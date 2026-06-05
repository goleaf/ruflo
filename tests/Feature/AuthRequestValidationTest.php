<?php

use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Requests\Auth\ResetUserPasswordRequest;
use Illuminate\Foundation\Http\FormRequest;

test('auth form requests expose the canonical rule sets', function () {
    $registerRequest = new RegisterUserRequest;
    $resetRequest = new ResetUserPasswordRequest;

    expect($registerRequest)->toBeInstanceOf(FormRequest::class)
        ->and($resetRequest)->toBeInstanceOf(FormRequest::class)
        ->and($registerRequest->authorize())->toBeTrue()
        ->and($resetRequest->authorize())->toBeTrue()
        ->and(RegisterUserRequest::baseRules())->toHaveKeys(['name', 'email', 'password'])
        ->and(ResetUserPasswordRequest::baseRules())->toHaveKey('password');
});

test('auth form requests expose translated attributes and messages', function () {
    expect(RegisterUserRequest::attributeNames())->toBe([
        'name' => __('auth.fields.name'),
        'email' => __('auth.fields.email'),
        'password' => __('auth.fields.password'),
    ])->and(RegisterUserRequest::messageLines())->toBe([
        'email.unique' => __('auth.validation.email_unique'),
    ])->and(ResetUserPasswordRequest::attributeNames())->toBe([
        'password' => __('auth.fields.password'),
    ]);
});
