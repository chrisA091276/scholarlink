<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$schema = \Illuminate\Support\Facades\DB::connection()->getSchemaBuilder();
$text = "# Data dictionary\n\nGenerated from the migrated XAMPP schema. Contains schema metadata only, never student records. Laravel migrations remain the source of truth.\n\n";
foreach ($schema->getTables($schema->getCurrentSchemaName()) as $table) {
    $name = $table['name'];
    $text .= "## `{$name}`\n\n| Column | Database type | Nullable |\n|---|---|---|\n";
    foreach ($schema->getColumns($name) as $column) $text .= '| `'.$column['name'].'` | `'.$column['type'].'` | '.($column['nullable'] ? 'Yes' : 'No')." |\n";
    $text .= "\nIndexes:\n\n";
    foreach ($schema->getIndexes($name) as $index) $text .= '- `'.$index['name'].'`: '.implode(', ', $index['columns']).($index['unique'] ? ' (unique)' : '')."\n";
    $foreign = $schema->getForeignKeys($name);
    if ($foreign) {
        $text .= "\nForeign keys:\n\n";
        foreach ($foreign as $key) $text .= '- '.implode(', ', $key['columns']).' → '.$key['foreign_table'].'('.implode(', ', $key['foreign_columns']).'); delete '.$key['on_delete'].".\n";
    }
    $text .= "\n";
}
file_put_contents(__DIR__.'/../docs/data-dictionary.md', $text);
echo "Schema metadata written to docs/data-dictionary.md\n";
