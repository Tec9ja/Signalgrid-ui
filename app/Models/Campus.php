<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campus extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'address',
        'locality',
        'country',
        'latitude',
        'longitude',
        'reference_source',
        'setup_completed_at',
        'setup_note',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'setup_completed_at' => 'datetime',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function buildings()
    {
        return $this->hasMany(Building::class);
    }

    public function mapEmbedUrl(): ?string
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        $lat = (float) $this->latitude;
        $lng = (float) $this->longitude;
        $bbox = implode(',', [
            $this->formatDegrees($lng - 0.008),
            $this->formatDegrees($lat - 0.008),
            $this->formatDegrees($lng + 0.008),
            $this->formatDegrees($lat + 0.008),
        ]);

        return 'https://www.openstreetmap.org/export/embed.html?bbox='.rawurlencode($bbox)
            .'&layer=mapnik&marker='.rawurlencode($this->formatDegrees($lat).','.$this->formatDegrees($lng));
    }

    public function googleMapsUrl(): ?string
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return 'https://www.google.com/maps?q='.$this->formatDegrees((float) $this->latitude).','.$this->formatDegrees((float) $this->longitude);
    }

    private function formatDegrees(float $value): string
    {
        return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
    }
}
