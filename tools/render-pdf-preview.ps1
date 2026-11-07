param([Parameter(Mandatory=$true)][string]$PdfPath)
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Runtime.WindowsRuntime
$null = [Windows.Storage.StorageFile,Windows.Storage,ContentType=WindowsRuntime]
$null = [Windows.Data.Pdf.PdfDocument,Windows.Data.Pdf,ContentType=WindowsRuntime]
$null = [Windows.Storage.Streams.InMemoryRandomAccessStream,Windows.Storage.Streams,ContentType=WindowsRuntime]
$null = [Windows.Data.Pdf.PdfPageRenderOptions,Windows.Data.Pdf,ContentType=WindowsRuntime]
$asTask = [System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object { $_.Name -eq 'AsTask' -and $_.IsGenericMethod -and $_.GetParameters().Count -eq 1 -and $_.GetParameters()[0].ParameterType.Name -eq 'IAsyncOperation`1' } | Select-Object -First 1
$actionTask = [System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object { $_.Name -eq 'AsTask' -and !$_.IsGenericMethod -and $_.GetParameters().Count -eq 1 -and $_.GetParameters()[0].ParameterType.Name -eq 'IAsyncAction' } | Select-Object -First 1
function Await-Operation($Operation, [Type]$ResultType) {
    $task = $asTask.MakeGenericMethod($ResultType).Invoke($null, @($Operation))
    $task.Wait()
    return $task.Result
}
$resolvedPdf = (Resolve-Path -LiteralPath $PdfPath).Path
$file = Await-Operation ([Windows.Storage.StorageFile]::GetFileFromPathAsync($resolvedPdf)) ([Windows.Storage.StorageFile])
$pdf = Await-Operation ([Windows.Data.Pdf.PdfDocument]::LoadFromFileAsync($file)) ([Windows.Data.Pdf.PdfDocument])
for ($index = 0; $index -lt $pdf.PageCount; $index++) {
    $page = $pdf.GetPage($index)
    $stream = New-Object Windows.Storage.Streams.InMemoryRandomAccessStream
    $options = New-Object Windows.Data.Pdf.PdfPageRenderOptions
    $options.DestinationWidth = 1190
    $renderTask = $actionTask.Invoke($null, @($page.RenderToStreamAsync($stream, $options)))
    $renderTask.Wait()
    $stream.Seek(0)
    $reader = [System.IO.WindowsRuntimeStreamExtensions]::AsStreamForRead($stream)
    $outputPath = [System.IO.Path]::Combine([System.IO.Path]::GetDirectoryName($resolvedPdf), [System.IO.Path]::GetFileNameWithoutExtension($resolvedPdf)) + '-page-' + ($index + 1) + '.png'
    $output = [System.IO.File]::Create($outputPath)
    $reader.CopyTo($output)
    $output.Dispose(); $reader.Dispose(); $stream.Dispose(); $page.Dispose()
    Write-Output $outputPath
}
