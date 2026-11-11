<?php

namespace App\Support;

/**
 * Published facts for the AAUA workspace.
 *
 * Campus point: the gazetteer decimal printed for Adekunle Ajasin University, 7.479234 N, 5.748411 E.
 * Postal address: P.M.B. 001, Akungba-Akoko. The WHED listing uses P.M.B. 01 for the same box.
 * Website and library name: https://www.aaua.edu.ng.
 * Buildings are limited to places named on those public pages.
 * Floors, faculties, centres, office holders and student counts are omitted.
 */
class AauaPublicRecord
{
    public const NAME = 'Adekunle Ajasin University, Akungba';

    public const SLUG = 'aaua-akungba';

    public static function payload(): array
    {
        $form = self::formState();

        return [
            'institution' => [
                'name' => $form['name'],
                'slug' => $form['slug'],
                'type' => $form['type'],
                'timezone' => $form['timezone'],
                'website' => $form['website'],
                'motto' => $form['motto'],
            ],
            'campus' => [
                'name' => $form['campus_name'],
                'code' => $form['campus_code'],
                'address' => $form['address'],
                'locality' => $form['locality'],
                'country' => $form['country'],
                'latitude' => $form['latitude'],
                'longitude' => $form['longitude'],
                'reference_source' => $form['reference_source'],
                'setup_note' => $form['setup_note'],
            ],
            'buildings' => $form['buildings'],
            'retire_unlisted' => true,
        ];
    }

    public static function formState(): array
    {
        return [
            'name' => self::NAME,
            'slug' => self::SLUG,
            'type' => 'university',
            'timezone' => 'Africa/Lagos',
            'website' => 'https://www.aaua.edu.ng',
            'motto' => 'For Learning and Service',
            'campus_name' => 'Akungba Campus',
            'campus_code' => 'AAUA',
            'address' => 'P.M.B. 001, Akungba-Akoko, Ondo State, Nigeria',
            'locality' => 'Akungba-Akoko, Ondo State',
            'country' => 'Nigeria',
            'latitude' => '7.479234',
            'longitude' => '5.748411',
            'reference_source' => 'public_record',
            'setup_note' => 'Public record. Postal address P.M.B. 001, Akungba-Akoko, Ondo State, Nigeria (also listed as P.M.B. 01). Website https://www.aaua.edu.ng. Motto: For Learning and Service. Campus coordinate 7.479234 N, 5.748411 E. Senate Building, the University Library and the Postgraduate School are named in public pages. Faculties, centres and library divisions are academic units, not surveyed buildings. Interior floors are not published.',
            'buildings' => [
                [
                    'name' => 'Senate Building',
                    'code' => 'SENATE',
                    'description' => 'Named in the university public photo records. Interior floors are not published.',
                ],
                [
                    'name' => 'University Library',
                    'code' => 'LIBRARY',
                    'description' => 'The University Library at Akungba-Akoko, named on the university library page. Interior floors are not published.',
                ],
                [
                    'name' => 'Postgraduate School',
                    'code' => 'PG',
                    'description' => 'Named in the university public photo records. Interior floors are not published.',
                ],
            ],
        ];
    }
}
