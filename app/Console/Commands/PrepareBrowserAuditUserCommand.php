<?php
namespace App\Console\Commands;
use App\Models\User;use Illuminate\Console\Command;use Illuminate\Support\Facades\Hash;
class PrepareBrowserAuditUserCommand extends Command{protected $signature='signalgrid:prepare-browser-audit-user {email} {password} {--role=Super Admin}';protected $description='Create or reset a local browser-audit account';public function handle():int{$email=strtolower(trim($this->argument('email')));$user=User::updateOrCreate(['email'=>$email],['name'=>'SignalGrid Browser Audit','password'=>Hash::make($this->argument('password')),'status'=>'active']);$user->syncRoles([$this->option('role')]);$this->info('Browser audit account prepared: '.$email);return self::SUCCESS;}}
