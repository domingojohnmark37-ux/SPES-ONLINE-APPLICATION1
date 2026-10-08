<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicantContactPesoController extends Controller
{
    public function index(Request $request): View
    {
        return view('applicant.contact-peso');
    }
}
