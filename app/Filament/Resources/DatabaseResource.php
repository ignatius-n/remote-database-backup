<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DatabaseResource\Actions\DatabaseBackupAction;
use App\Filament\Resources\DatabaseResource\Pages;
use App\Filament\Resources\DatabaseResource\Services\Backup\AbstractBackupRunner;
use App\Filament\Resources\DatabaseResource\Services\Backup\BackupRunner;
use App\Models\Database;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DatabaseResource extends Resource
{
    protected static ?string $model = Database::class;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required(),
                Select::make('cron')
                    ->options([
                        '* * * * *' => 'Every Minute',
                        '0 * * * *' => 'Every Hour',
                        '0 0 * * *' => 'Every 00:00',
                    ]),
                Builder::make('data')
                    ->columnSpanFull()
                    ->blocks(
                        collect(BackupRunner::all())
                            ->map(
                                /**
                                 * @var AbstractBackupRunner $runner
                                 */
                                fn (string $runner) => $runner::getFilamentBlockComponent()
                            )
                            ->toArray()
                    )
                    ->maxItems(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('cron')
                    ->sortable()
                    ->searchable(),

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                DatabaseBackupAction::make(),
                Tables\Actions\ReplicateAction::make()
                    ->form(function (Form $form) {
                        return DatabaseResource::form($form);
                    })
                    ->beforeReplicaSaved(function (Database $replica, array $data) {
                        $replica->fill($data);
                    }),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDatabases::route('/'),
            'create' => Pages\CreateDatabase::route('/create'),
            'edit' => Pages\EditDatabase::route('/{record}/edit'),
            'backup-histories' => Pages\BackupHistory::route('/{record}/backup-histories'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\EditDatabase::class,
            Pages\BackupHistory::class,
        ]);
    }
}
