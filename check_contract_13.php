<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = 13;
$c = App\Domain\Contracts\Models\Contract::with(['documents'])->find($id);

if (!$c) {
    echo "Contract {$id} NOT FOUND.\n";
    exit(1);
}

echo "Contract ID: {$c->id}\n";
echo "Name: {$c->name}\n";

$doc = $c->documents->first();
if ($doc) {
    echo "Document ID: {$doc->id}\n";
    echo "File Name: {$doc->file_name}\n";
    $len = strlen($doc->extracted_text ?? '');
    echo "Extracted Text Length: {$len} chars\n";
    if ($len === 0) {
        echo "WARNING: No extracted text found in document.\n";
    }
} else {
    echo "WARNING: No document associated with this contract.\n";
}

$embeddingsCount = \Illuminate\Support\Facades\DB::table('contract_embeddings')
    ->where('contract_id', $id)
    ->count();

echo "Embeddings Count: {$embeddingsCount}\n";

if ($embeddingsCount === 0) {
    echo "WARNING: No embeddings generated.\n";
} else {
    echo "SUCCESS: Embeddings generated.\n";
}
