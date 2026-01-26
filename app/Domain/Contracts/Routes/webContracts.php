<?php

declare(strict_types=1);

use App\Domain\Contracts\Controllers\ContractController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    // Lista e criação de contratos
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/create', [ContractController::class, 'create'])->name('contracts.create');
    Route::post('/contracts', [ContractController::class, 'store'])->name('contracts.store');
    
    // Extração de dados com IA
    Route::post('/contracts/extract-data', [ContractController::class, 'extractData'])->name('contracts.extract-data');
    
    // Extração de dados de NF em PDF com IA
    Route::post('/contracts/extract-invoice-pdf', [ContractController::class, 'extractInvoiceDataFromPdf'])->name('contracts.extract-invoice-pdf');
    
    // Busca dados CNPJ na API pública
    Route::post('/contracts/fetch-cnpj', [ContractController::class, 'fetchCnpjData'])->name('contracts.fetch-cnpj');
    
    // Detalhes, edição e exclusão
    Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
    Route::get('/contracts/{contract}/edit', [ContractController::class, 'edit'])->name('contracts.edit');
    Route::put('/contracts/{contract}', [ContractController::class, 'update'])->name('contracts.update');
    Route::delete('/contracts/{contract}', [ContractController::class, 'destroy'])->name('contracts.destroy');
    
    // Upload de documentos
    Route::post('/contracts/{contract}/documents', [ContractController::class, 'uploadDocument'])->name('contracts.documents.upload');
    
    // Download de documento
    Route::get('/contract-documents/{document}/download', [ContractController::class, 'downloadDocument'])->name('contracts.documents.download');
    
    // Assistente Jurídico IA
    Route::post('/contracts/{contract}/ask-assistant', [ContractController::class, 'askAssistant'])->name('contracts.ask-assistant');
    
    // Alertas
    Route::get('/contracts/get-user-email', [ContractController::class, 'getUserEmail'])->name('contracts.get-user-email');
    Route::post('/contracts/{contract}/alerts', [ContractController::class, 'storeAlert'])->name('contracts.alerts.store');
    Route::put('/contract-alerts/{alert}', [ContractController::class, 'updateAlert'])->name('contracts.alerts.update');
    Route::delete('/contract-alerts/{alert}', [ContractController::class, 'destroyAlert'])->name('contracts.alerts.destroy');
    Route::post('/contract-alerts/{alert}/toggle', [ContractController::class, 'toggleAlert'])->name('contracts.alerts.toggle');
    Route::post('/contract-alerts/{alert}/create-task', [ContractController::class, 'createTaskFromAlert'])->name('contract-alerts.create-task');
    
    // Comentários
    Route::post('/contracts/{contract}/comments', [ContractController::class, 'storeComment'])->name('contracts.comments.store');
    Route::delete('/contract-comments/{comment}', [ContractController::class, 'destroyComment'])->name('contracts.comments.destroy');
    
    // Notas Fiscais
    Route::post('/contracts/{contract}/invoices', [ContractController::class, 'uploadInvoice'])->name('contracts.invoices.upload');
    Route::post('/contracts/{contract}/invoices/import-csv', [ContractController::class, 'importInvoicesCsv'])->name('contracts.invoices.import-csv');
    Route::put('/invoices/{invoice}', [ContractController::class, 'updateInvoice'])->name('invoices.update');
    Route::get('/invoices/{invoice}/pdf', [ContractController::class, 'downloadInvoicePdf'])->name('invoices.download-pdf');
    Route::get('/invoices/{invoice}/xml', [ContractController::class, 'downloadInvoiceXml'])->name('invoices.download-xml');
    Route::delete('/invoices/{invoice}', [ContractController::class, 'destroyInvoice'])->name('invoices.destroy');
});
