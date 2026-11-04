<?php
namespace App\Http\Controllers;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
class DashboardController extends Controller {public function index(Request $r):View{return view('dashboard',['pageTitle'=>'SignalGrid Network Intelligence','isDemonstration'=>true,'lastUpdatedAt'=>now(),'displayName'=>$r->user()->name,'displayRole'=>$r->user()->getRoleNames()->first()??'Viewer']);}}
