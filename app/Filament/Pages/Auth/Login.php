<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

/**
 * На демо-стенде форма входа в админку уже заполнена демо-доступом.
 */
class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        if (config('ideajob.demo')) {
            $this->form->fill([
                'email' => 'admin@example.com',
                'password' => 'password',
                'remember' => true,
            ]);
        }
    }
}
