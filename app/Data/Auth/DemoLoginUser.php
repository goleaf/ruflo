<?php

namespace App\Data\Auth;

final readonly class DemoLoginUser
{
    public function __construct(
        public string $name,
        public string $email,
        public string $role,
        public string $description,
        public string $password,
    ) {}

    /**
     * @return array{name: string, email: string, role: string, description: string, password: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'description' => $this->description,
            'password' => $this->password,
        ];
    }
}
