<?php

use App\Services\RichTextSanitizer;

it('keeps formatting produced by the report editor toolbar', function () {
    $sanitizer = new RichTextSanitizer;

    $html = '<p>Hasil <strong>bagus</strong>, tidak ada <em>kendala</em>.</p><ul><li>Item satu</li><li>Item dua</li></ul>';

    expect($sanitizer->sanitize($html))->toBe($html);
});

it('strips script tags and their content', function () {
    $sanitizer = new RichTextSanitizer;

    $result = $sanitizer->sanitize('<p>Report</p><script>alert("xss")</script>');

    expect($result)
        ->toContain('<p>Report</p>')
        ->not->toContain('<script>')
        ->not->toContain('alert');
});

it('unwraps disallowed tags but keeps their text content', function () {
    $sanitizer = new RichTextSanitizer;

    $result = $sanitizer->sanitize('<div onclick="steal()"><span style="color:red">Catatan penting</span></div>');

    expect($result)
        ->toContain('Catatan penting')
        ->not->toContain('<div')
        ->not->toContain('<span')
        ->not->toContain('onclick');
});

it('drops javascript: links but keeps safe ones', function () {
    $sanitizer = new RichTextSanitizer;

    $result = $sanitizer->sanitize('<p><a href="javascript:alert(1)">klik</a> <a href="https://example.com">aman</a></p>');

    expect($result)
        ->not->toContain('javascript:')
        ->toContain('href="https://example.com"')
        ->toContain('target="_blank"')
        ->toContain('rel="noopener noreferrer nofollow"');
});

it('returns null for empty or whitespace-only input', function () {
    $sanitizer = new RichTextSanitizer;

    expect($sanitizer->sanitize(null))->toBeNull();
    expect($sanitizer->sanitize(''))->toBeNull();
    expect($sanitizer->sanitize('   '))->toBeNull();
});
