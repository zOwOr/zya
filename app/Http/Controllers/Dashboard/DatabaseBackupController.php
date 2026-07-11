<?php

namespace App\Http\Controllers\Dashboard;

use File;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

class DatabaseBackupController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'database';

    protected array $permissionMapping = [
        'index' => 'read',
        'create' => 'create',
        'download' => 'read',
        'delete' => 'delete',
    ];

    public function __construct()
    {
        $this->initializeModulePermission();
    }

    public function index()
    {
        return view('database.index', [
            'files' => File::allFiles(storage_path('/app/POS'))
        ]);
    }

    // Backup database is not working, and you need to enter manually in terminal with command php artisan backup:run.
    public function create(){
        \Artisan::call('backup:run');

        return Redirect::route('backup.index')->with('success', 'Database Backup Successfully!');
    }

    public function download(String $getFileName)
    {
        $path = storage_path('app\POS/' . $getFileName);

        return response()->download($path);
    }

    public function delete(String $getFileName)
    {
        Storage::delete('POS/' . $getFileName);

        return Redirect::route('backup.index')->with('success', 'Database Deleted Successfully!');
    }
}
