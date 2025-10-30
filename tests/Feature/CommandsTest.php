<?php

use CleaniqueCoders\Eligify\Eligify;
use CleaniqueCoders\Eligify\Models\AuditLog;
use CleaniqueCoders\Eligify\Models\Criteria;
use CleaniqueCoders\Eligify\Models\Evaluation;
use CleaniqueCoders\Eligify\Models\Rule;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Clean up any existing data
    Criteria::query()->delete();
    Rule::query()->delete();
    Evaluation::query()->delete();
    AuditLog::query()->delete();
});

test('eligify command shows status', function () {
    // Create some test data
    $criteria = Eligify::criteria('test_criteria')
        ->addRule('field1', '>=', 10)
        ->save();

    $this->artisan('eligify', ['action' => 'status'])
        ->expectsOutputToContain('Eligify Package Status')
        ->expectsOutputToContain('Criteria')
        ->expectsOutputToContain('Rules')
        ->expectsOutputToContain('Evaluations')
        ->expectsOutputToContain('Audit Logs')
        ->assertExitCode(0);
});

test('eligify command shows statistics', function () {
    // Create test data
    Eligify::criteria('test_criteria')
        ->addRule('field1', '>=', 10)
        ->addRule('field2', '==', 'value')
        ->save();

    $this->artisan('eligify', ['action' => 'stats'])
        ->expectsOutputToContain('Eligify Statistics')
        ->expectsOutputToContain('Total Criteria')
        ->expectsOutputToContain('Total Rules')
        ->assertExitCode(0);
});

test('eligify command runs health check', function () {
    $this->artisan('eligify', ['action' => 'health'])
        ->expectsOutputToContain('Health Check')
        ->assertExitCode(0);
});

test('eligify command shows help for unknown action', function () {
    $this->artisan('eligify', ['action' => 'unknown'])
        ->expectsOutputToContain('Available commands')
        ->assertExitCode(0);
});

test('eligify command defaults to status', function () {
    $this->artisan('eligify')
        ->expectsOutputToContain('Eligify Package Status')
        ->assertExitCode(0);
});

test('eligify criteria list command filters active criteria', function () {
    Eligify::criteria('active_criteria')
        ->active(true)
        ->addRule('field', '>=', 1)
        ->save();

    Eligify::criteria('inactive_criteria')
        ->active(false)
        ->addRule('field', '>=', 1)
        ->save();

    $this->artisan('eligify:criteria', [
        'action' => 'list',
        '--active' => true,
    ])
        ->expectsOutputToContain('active_criteria')
        ->assertExitCode(0);
});

test('eligify criteria list command shows message when no criteria found', function () {
    $this->artisan('eligify:criteria', ['action' => 'list'])
        ->expectsOutputToContain('No criteria found')
        ->assertExitCode(0);
});

test('eligify criteria list command outputs json format', function () {
    Eligify::criteria('test_criteria')
        ->addRule('field', '>=', 1)
        ->save();

    $this->artisan('eligify:criteria', [
        'action' => 'list',
        '--format' => 'json',
    ])
        ->expectsOutputToContain('test_criteria')
        ->assertExitCode(0);
});

test('eligify criteria show command handles non-existent criteria', function () {
    $this->artisan('eligify:criteria', [
        'action' => 'show',
        'criteria' => 'non_existent',
    ])
        ->expectsOutputToContain('not found')
        ->assertExitCode(1);
});

test('eligify criteria delete command removes criteria with confirmation', function () {
    $criteria = Eligify::criteria('test_criteria')
        ->addRule('field', '>=', 1)
        ->save();

    expect(Criteria::where('slug', 'test-criteria')->exists())->toBeTrue();

    $this->artisan('eligify:criteria', [
        'action' => 'delete',
        'criteria' => 'test_criteria',
        '--force' => true,
    ])
        ->expectsOutputToContain('deleted successfully')
        ->assertExitCode(0);

    expect(Criteria::where('slug', 'test-criteria')->exists())->toBeFalse();
});

test('eligify criteria delete command handles non-existent criteria', function () {
    $this->artisan('eligify:criteria', [
        'action' => 'delete',
        'criteria' => 'non_existent',
        '--force' => true,
    ])
        ->expectsOutputToContain('not found')
        ->assertExitCode(1);
});

test('eligify criteria export command exports to json file', function () {
    $criteria = Eligify::criteria('test_export')
        ->description('Test export criteria')
        ->addRule('field1', '>=', 10)
        ->addRule('field2', '==', 'value')
        ->save();

    $exportFile = storage_path('test_export.json');

    // Clean up if file exists
    if (File::exists($exportFile)) {
        File::delete($exportFile);
    }

    $this->artisan('eligify:criteria', [
        'action' => 'export',
        'criteria' => 'test_export',
        '--file' => $exportFile,
        '--format' => 'json',
    ])
        ->expectsOutputToContain('Exported')
        ->assertExitCode(0);

    expect(File::exists($exportFile))->toBeTrue();

    $content = json_decode(File::get($exportFile), true);
    expect($content)->toHaveKey('criteria');
    expect($content['criteria'])->toHaveCount(1);

    // Clean up
    File::delete($exportFile);
});

test('eligify evaluate command with inline json data', function () {
    $criteria = Eligify::criteria('test_eval')
        ->addRule('score', '>=', 70)
        ->save();

    $this->artisan('eligify:evaluate', [
        'criteria' => 'test_eval',
        '--data' => '{"score": 85}',
    ])
        ->expectsOutputToContain('PASSED')
        ->expectsOutputToContain('Score')
        ->assertExitCode(0);
});

test('eligify evaluate command handles non-existent criteria', function () {
    $this->artisan('eligify:evaluate', [
        'criteria' => 'non_existent',
        '--data' => '{"score": 85}',
    ])
        ->expectsOutputToContain('not found')
        ->assertExitCode(1);
});

test('eligify evaluate command handles invalid json data', function () {
    $criteria = Eligify::criteria('test_eval')
        ->addRule('score', '>=', 70)
        ->save();

    $this->artisan('eligify:evaluate', [
        'criteria' => 'test_eval',
        '--data' => 'invalid json',
    ])
        ->assertExitCode(1);
});

test('eligify evaluate command with file data', function () {
    $criteria = Eligify::criteria('test_eval')
        ->addRule('score', '>=', 70)
        ->save();

    $dataFile = storage_path('test_data.json');
    File::put($dataFile, json_encode(['score' => 85]));

    $this->artisan('eligify:evaluate', [
        'criteria' => 'test_eval',
        '--file' => $dataFile,
    ])
        ->expectsOutputToContain('PASSED')
        ->expectsOutputToContain('Score')
        ->assertExitCode(0);

    // Clean up
    File::delete($dataFile);
});

test('eligify evaluate command handles missing data file', function () {
    $criteria = Eligify::criteria('test_eval')
        ->addRule('score', '>=', 70)
        ->save();

    $this->artisan('eligify:evaluate', [
        'criteria' => 'test_eval',
        '--file' => '/non/existent/file.json',
    ])
        ->assertExitCode(1);
});

test('eligify evaluate command outputs json format', function () {
    $criteria = Eligify::criteria('test_eval')
        ->addRule('score', '>=', 70)
        ->save();

    $this->artisan('eligify:evaluate', [
        'criteria' => 'test_eval',
        '--data' => '{"score": 85}',
        '--format' => 'json',
    ])
        ->assertExitCode(0);
});

test('eligify evaluate command warns about inactive criteria', function () {
    $criteria = Eligify::criteria('inactive_criteria')
        ->active(false)
        ->addRule('score', '>=', 70)
        ->save();

    $this->artisan('eligify:evaluate', [
        'criteria' => 'inactive_criteria',
        '--data' => '{"score": 85}',
    ])
        ->expectsOutputToContain('inactive')
        ->assertExitCode(0);
});

test('eligify evaluate command with verbose output', function () {
    $criteria = Eligify::criteria('test_eval')
        ->addRule('score', '>=', 70)
        ->save();

    $this->artisan('eligify:evaluate', [
        'criteria' => 'test_eval',
        '--data' => '{"score": 85}',
        '--verbose-output' => true,
    ])
        ->expectsOutputToContain('PASSED')
        ->expectsOutputToContain('Criteria UUID')
        ->assertExitCode(0);
});

test('eligify evaluate command with batch processing', function () {
    $criteria = Eligify::criteria('test_eval')
        ->addRule('score', '>=', 70)
        ->save();

    $dataFile = storage_path('test_batch.json');
    File::put($dataFile, json_encode([
        ['score' => 85],
        ['score' => 65],
        ['score' => 90],
    ]));

    $this->artisan('eligify:evaluate', [
        'criteria' => 'test_eval',
        '--file' => $dataFile,
        '--batch' => true,
    ])
        ->expectsOutputToContain('Batch');

    // Clean up
    File::delete($dataFile);
});

test('eligify command help shows available actions', function () {
    $this->artisan('eligify', ['action' => 'help'])
        ->expectsOutputToContain('Available commands')
        ->assertExitCode(0);
});

test('eligify criteria command handles unknown action', function () {
    $this->artisan('eligify:criteria', ['action' => 'unknown'])
        ->expectsOutputToContain('Available actions')
        ->assertExitCode(0);
});
