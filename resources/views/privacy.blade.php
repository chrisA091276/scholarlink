@extends('layouts.guest')
@section('title', 'Privacy notice and application terms')
@section('content')
<h1 class="h4">ScholarLink privacy notice and terms</h1>
<p class="small text-muted">Application notice version: October 26, 2026</p>
<p>ScholarLink collects identity, contact, enrollment and academic information to process scholarship applications. Financial information and supporting documents are requested only where the selected program requires them.</p>
<p>Your records are accessible to you, authorized administrators, and staff assigned to assess your application. Documents are private. Service providers may process stored files or deliver account messages as configured by the school.</p>
<p>You must provide accurate information and submit only documents you are authorized to share. An application is not a guarantee of an award. Decisions and corrections remain recorded in the review history.</p>
<p>You may correct permitted records through your profile or the correction workflow. For requests to access, correct, or remove retained information, contact the school's scholarship office through its official channels. Identity and authorization must be verified before records are released.</p>
<p>Records must be retained only for the institution's approved scholarship, audit and legal purposes. Production operators must publish the responsible privacy contact, approved retention period and applicable processing basis before collecting real student data.</p>
<a href="{{ route('register') }}" class="btn btn-primary">Return to registration</a>
@endsection
