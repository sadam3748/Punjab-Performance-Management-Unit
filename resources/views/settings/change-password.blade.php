@extends('layouts.app')
@section('title','Change Password')
@section('page_title','Change Password')
@section('content')
<div class="card-ppmf"><div class="card-ppmf-header"><div class="card-ppmf-title"><i class="bi bi-key"></i> Update Password</div></div><div class="card-ppmf-body"><form><div class="mb-3"><label class="form-label">Current Password</label><input type="password" class="form-control"></div><div class="mb-3"><label class="form-label">New Password</label><input type="password" class="form-control"></div><div class="mb-3"><label class="form-label">Confirm Password</label><input type="password" class="form-control"></div><button type="button" class="btn btn-success">Update Password</button></form></div></div>
@endsection
