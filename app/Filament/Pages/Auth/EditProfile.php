<?php

namespace App\Filament\Pages\Auth;

use Apriansyahrs\MekayaTheme\Auth\MekayaEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class EditProfile extends MekayaEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('avatar_url')
                    ->label('Foto Profil')
                    ->avatar()
                    ->alignCenter()
                    ->directory('avatars')
                    ->disk('public')
                    ->visibility('public')
                    ->imageEditor()
                    ->circleCropper()
                    ->maxSize(2048),
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
            ]);
    }
}
