@php($notices = json_decode(file_get_contents(resource_path('data/adfc-notices.json')), true))
<div class="adfc-notices">
  <details><summary>Qualification requirements</summary><p>{{ $notices['qualification'] }}</p><p class="text-muted">The TDP requirements above are reproduced from the supplied ADFC form. Your selected scholarship's published criteria also apply.</p></details>
  <details><summary>Documentary requirements</summary><p>{{ $notices['documents'] }}</p></details>
  <details open><summary>Privacy notice</summary><p>{{ $notices['privacy'] }}</p></details>
  <details open><summary>Consent</summary>@foreach($notices['consent'] as $paragraph)<p>{{ $paragraph }}</p>@endforeach</details>
</div>
