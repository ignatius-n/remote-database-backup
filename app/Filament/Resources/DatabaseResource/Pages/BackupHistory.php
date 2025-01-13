<?php

namespace App\Filament\Resources\DatabaseResource\Pages;

use App\Filament\Resources\DatabaseResource;
use App\Models\BackupHistory as ModelsBackupHistory;
use App\Models\Database;
use Exception;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class BackupHistory extends ManageRelatedRecords
{
    protected static string $resource = DatabaseResource::class;

    protected static string $relationship = 'backup_histories';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return 'Backup Hostories';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('filename')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('filename')
            ->columns([
                Tables\Columns\TextColumn::make('filename'),
                Tables\Columns\TextColumn::make('filesize')
                    ->formatStateUsing(fn(int $state) => $this->formatSizeUnits($state)),
                Tables\Columns\TextColumn::make('created_at'),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->url(fn(\App\Models\BackupHistory $record) => url('/download/' . $record->filename))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (ModelsBackupHistory $record) {
                        try {
                            unlink(storage_path('databases/' . $record->filename));
                        } catch (Exception $e) {
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            $records->each(
                                function (ModelsBackupHistory $record) {
                                    try {
                                        unlink(storage_path('databases/' . $record->filename));
                                    } catch (Exception $e) {
                                    }
                                }
                            );
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    function formatSizeUnits($bytes): string
    {
        if ($bytes >= 1099511627776) {
            $bytes = number_format($bytes / 1099511627776, 2) . ' TB';
        } elseif ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
}
