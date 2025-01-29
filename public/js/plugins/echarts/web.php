<?php

use App\Http\Controllers\AssetsTagController;
use App\Http\Controllers\PlaningDataController;
use App\Http\Controllers\UserHistoryController;
use App\Http\Controllers\UserRoleController;
use App\Models\Attachment;
use App\Models\BscRncSite;
use App\Models\BtsSite;
use App\Models\CoreSites;
use App\Models\MwTxSite;
use App\Models\OfTxSite;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\btsSitesController;
use App\Http\Controllers\ColocatedController;
use App\Http\Controllers\CoreSitesController;
use App\Http\Controllers\MwTxSitesController;
use App\Http\Controllers\OfTxSitesController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PartNumberController;
use App\Http\Controllers\BscRncSitesController;
use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\PartNumberCategoriesController;
use App\Http\Controllers\MoveOrderController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

Auth::routes();
Route::get('page_is_not_accessable', function () {
    return view('page_not_accessable');
})->name('page_is_not_accessable');

Route::group(['middleware' => ['auth']], function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');
    Route::get('/home', function () {
        return view('welcome');
    })->name('home2');
});

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::post('/login', [LoginController::class, 'authenticate'])->name('login');
Route::group(['middleware' => 'CheckUserRoleAccessItems'], function () {
    Route::group(['prefix' => 'User', 'middleware' => ['auth']], function () {
        Route::get('index', [UserController::class, 'index'])->name('User.index');
        Route::get('create', [UserController::class, 'create'])->name('User.create');
        Route::post('store', [UserController::class, 'store'])->name('User.store');
        Route::post('index', [UserController::class, 'index'])->name('User.index-post');
        Route::get('show/{id}', [UserController::class, 'show'])->name('User.show');
        Route::get('edit/{id}', [UserController::class, 'edit'])->name('User.edit');
        Route::patch('update/{id}', [UserController::class, 'update'])->name('User.update');
        Route::post('/destroy/{id}', [UserController::class, 'destroy'])->name('User.destroy');
        //Route::resource('User/', UserController::class);
        // Route::get('import_users_from_atlas', [UserController::class, 'import_users_from_atlas']);
        Route::get('edit_profile', [UserController::class, 'edit_profile'])->name('User.edit_profile');
        Route::get('change_password', function (Request $request) {
            return view('auth.passwords.reset', ['reset_code' => '']);
        })->name('User.reset');
        Route::get('bulk_upload', [UserController::class, 'bulk_upload'])->name('User.bulk_upload');
        Route::post('bulk_upload', [UserController::class, 'bulk_upload'])->name('User.bulk_upload_post');
        Route::get('bulk_upload_update', [UserController::class, 'bulk_upload_update'])->name('User.bulk_update');
        Route::post('bulk_upload_update', [UserController::class, 'bulk_upload_update'])->name('User.bulk_update_post');
        Route::get('bulk_upload_cell_site', [UserController::class, 'bulk_upload_cell_site'])->name('User.bulk_upload_cell_site');
        Route::post('bulk_upload_cell_site', [UserController::class, 'bulk_upload_cell_site'])->name('User.bulk_upload_cell_site_post');

    });

    Route::group(['prefix' => 'PartNumber', 'middleware' => ['auth']], function ($index) {
        Route::get('/', [PartNumberController::class, 'index'])->name('PartNumber');
        Route::get('index', [PartNumberController::class, 'index'])->name('PartNumber.index');
        Route::post('index', [PartNumberController::class, 'index'])->name('PartNumber.index_post');
        Route::get('/create', [PartNumberController::class, 'create'])->name('PartNumber.create');
        Route::post('/store', [PartNumberController::class, 'store'])->name('PartNumber.store');
        Route::post('/destroy/{id}', [PartNumberController::class, 'destroy'])->name('PartNumber.destroy');
        Route::get('show/{id}', [PartNumberController::class, 'show'])->name('PartNumber.show');
        Route::get('edit/{id}', [PartNumberController::class, 'edit'])->name('PartNumber.edit');
        Route::post('update/{id}', [PartNumberController::class, 'update'])->name('PartNumber.update');
        Route::get('bulk_upload', [PartNumberController::class, 'bulk_upload'])->name('PartNumber.bulk_upload');
        Route::post('bulk_upload', [PartNumberController::class, 'bulk_upload'])->name('PartNumber.bulk_upload_post');
        Route::post('download', [PartNumberController::class, 'download'])->name('PartNumber.download');
    });
    Route::group(['prefix' => 'PartNumberCategories', 'middleware' => ['auth']], function () {
        Route::get('index', [PartNumberCategoriesController::class, 'getCategory'])->name('PartNumberCategories.getCategory');
        Route::get('create/', [PartNumberCategoriesController::class, 'create'])->name('PartNumberCategories.create');
        Route::post('create/', [PartNumberCategoriesController::class, 'create'])->name('PartNumberCategories.createpost');
        Route::post('create_sub_category/{level?}', [PartNumberCategoriesController::class, 'create_sub_category'])->name('PartNumberCategories.create_sub_category');
        Route::get('edit/{id?}', [PartNumberCategoriesController::class, 'edit'])->name('PartNumberCategories.edit');
        Route::post('update/{id?}', [PartNumberCategoriesController::class, 'update'])->name('PartNumberCategories.update');
        Route::get('create_sub_category/{level?}', [PartNumberCategoriesController::class, 'create_sub_category'])->name('PartNumberCategories.create_sub_category');
        Route::post('loadAttributte', [PartNumberCategoriesController::class, 'loadAttributte'])->name('PartNumberCategories.loadAttributte');
        Route::post('getcategory/{level?}/{parent_id?}', [PartNumberCategoriesController::class, 'getCategory'])->name('PartNumberCategories.getCategorypost');
        Route::get('getcategory/{level?}/{parent_id?}', [PartNumberCategoriesController::class, 'getCategory'])->name('PartNumberCategories.getCategory');
        Route::post('store/{level?}', [PartNumberCategoriesController::class, 'store'])->name('PartNumberCategories.store');
        Route::post('/load_category', [PartNumberCategoriesController::class, 'load_category'])->name('PartNumberCategories.load_category');
        Route::post('/search_form_load_category', [PartNumberCategoriesController::class, 'search_form_load_category'])->name('PartNumberCategories.search_form_load_category');
        Route::post('/destroy/{id}', [PartNumberCategoriesController::class, 'destroy'])->name('PartNumberCategories.destroy');
        Route::get('show/{id}', [PartNumberCategoriesController::class, 'show'])->name('PartNumberCategories.show');
        Route::get('bulk_upload/{level}', [PartNumberCategoriesController::class, 'bulk_upload'])->name('PartNumberCategories.bulk_upload');
        Route::post('bulk_upload/{level}', [PartNumberCategoriesController::class, 'bulk_upload'])->name('PartNumberCategories.bulk_upload_post');
        Route::post('load_sub_category', [PartNumberCategoriesController::class, 'load_sub_category'])->name('PartNumberCategories.load_sub_category');
    });

    Route::group(['prefix' => 'Manufacturer', 'middleware' => ['auth']], function () {
        Route::get('index', [ManufacturerController::class, 'index'])->name('ManufacturerController.index');
        Route::post('index', [ManufacturerController::class, 'index'])->name('ManufacturerController.index_post');
        Route::get('create', [ManufacturerController::class, 'create'])->name('ManufacturerController.create');
        Route::post('store', [ManufacturerController::class, 'store'])->name('ManufacturerController.store');
        Route::post('/destroy/{id}', [ManufacturerController::class, 'destroy'])->name('ManufacturerController.destroy');
        Route::get('edit/{id}', [ManufacturerController::class, 'edit'])->name('ManufacturerController.edit');
        Route::post('edit/{id}', [ManufacturerController::class, 'edit'])->name('ManufacturerController.edit_post');
    });
    Route::group(['prefix' => 'Warehouse', 'middleware' => ['auth']], function () {
        Route::get('index', [WarehouseController::class, 'index'])->name('Warehouse.index');
        Route::post('index', [WarehouseController::class, 'index'])->name('Warehouse.index_post');
        Route::get('create', [WarehouseController::class, 'create'])->name('Warehouse.create');
        Route::post('store', [WarehouseController::class, 'store'])->name('Warehouse.store');
        Route::post('/destroy/{id}', [WarehouseController::class, 'destroy'])->name('Warehouse.destroy');
        Route::get('edit/{id}', [WarehouseController::class, 'edit'])->name('Warehouse.edit');
        Route::post('edit/{id}', [WarehouseController::class, 'edit'])->name('Warehouse.edit_post');
    });
    Route::group(['prefix' => 'Asset', 'middleware' => ['auth']], function () {
        Route::get('index', [AssetController::class, 'index'])->name('Asset.index');
        Route::post('index', [AssetController::class, 'index'])->name('Asset.index_post');
        Route::get('get_site_id', [AssetController::class, 'get_site_id'])->name('Asset.get_site_id');
        Route::post('get_site_id', [AssetController::class, 'get_site_id'])->name('Asset.get_site_id_post');
        Route::get('create', [AssetController::class, 'create'])->name('Asset.create');
        Route::post('store', [AssetController::class, 'store'])->name('Asset.store');
        Route::post('store_web', [AssetController::class, 'storeWeb'])->name('Asset.store_web');
        Route::post('/destroy/{id}', [AssetController::class, 'destroy'])->name('Asset.destroy');
        Route::get('verify_edit/{id}', [AssetController::class, 'verify_edit'])->name('Asset.verify_edit');
        Route::get('edit_change_site/{id}', [AssetController::class, 'edit_change_site'])->name('Asset.edit_change_site');
        Route::get('edit_change_qr_code/{id}', [AssetController::class, 'edit_change_qr_code'])->name('Asset.edit_change_qr_code');
        Route::get('edit/{id}', [AssetController::class, 'edit'])->name('Asset.edit');
        Route::post('edit/{id}', [AssetController::class, 'edit'])->name('Asset.edit_post');
        Route::post('update/{id}', [AssetController::class, 'update'])->name('Asset.update');
        Route::post('get_part_numbers', [AssetController::class, 'get_part_numbers'])->name('Asset.get_part_numbers');
        Route::post('get_part_number', [AssetController::class, 'get_part_number'])->name('Asset.get_part_number');
        Route::get('show/{id}', [AssetController::class, 'show'])->name('Asset.show');
        Route::post('download', [AssetController::class, 'download'])->name('Asset.download');
        Route::delete('destroy_attachment', [AssetController::class, 'destroy_attachment'])->name('Asset.destroy_attachment');
        Route::get('show_all_history', [AssetController::class, 'show_all_history'])->name('Asset.show_all_history');
        Route::post('show_all_history', [AssetController::class, 'show_all_history'])->name('Asset.show_all_history_post');
        Route::post('download_history', [AssetController::class, 'download_history'])->name('Asset.download_history');
        Route::post('getCells', [AssetController::class, 'getCells'])->name('Asset.getCells');
        Route::get('history_validate', [AssetController::class, 'history_validate'])->name('Asset.history_validate');
        Route::get('bulk_status_update', [AssetController::class, 'bulk_status_update'])->name('Asset.bulk_status_update');
        Route::post('bulk_status_update', [AssetController::class, 'bulk_status_update'])->name('Asset.bulk_status_update_post');
        Route::get('bulk_serial_number_update', [AssetController::class, 'bulk_serial_number_update'])->name('Asset.bulk_serial_number_update');
        Route::post('bulk_serial_number_update', [AssetController::class, 'bulk_serial_number_update'])->name('Asset.bulk_serial_number_update_post');
        Route::get('bulk_delete', [AssetController::class, 'bulk_delete'])->name('Asset.bulk_delete');
        Route::post('bulk_delete', [AssetController::class, 'bulk_delete'])->name('Asset.bulk_delete_post');
        Route::post('/get_part_numbers_list_text', [AssetController::class, 'get_part_numbers_list_text'])->name('Asset.get_part_numbers_list_text');

        Route::post('store_change_site', [AssetController::class, 'store_change_site'])->name('Asset.store_change_site');
        Route::get('update_mtilt', [AssetController::class, 'update_mtilt'])->name('Asset.update_mtilt');
    });

    Route::group(['prefix' => 'Assetstag', 'middleware' => ['auth']], function () {

        Route::get('/', [AssetsTagController::class, 'index'])->name('AssetsTag.index');
        Route::post('/', [AssetsTagController::class, 'index'])->name('AssetsTag.index_post');
        Route::get('create', [AssetsTagController::class, 'create'])->name('AssetsTag.create');
        Route::post('create', [AssetsTagController::class, 'create'])->name('AssetsTag.create_post');
        Route::get('QRcodecorrection', [AssetsTagController::class, 'QRcodecorrection'])->name('AssetsTag.QRcodecorrection');
        Route::post('QRcodecorrection', [AssetsTagController::class, 'QRcodecorrection'])->name('AssetsTag.QRcodecorrection_post');
        Route::get('QRcodePage', [AssetsTagController::class, 'QRcodePage'])->name('AssetsTag.QRcodePage');
        Route::post('QRcodePage', [AssetsTagController::class, 'QRcodePage'])->name('AssetsTag.QRcodePage_post');
        Route::get('ValidateQR', [AssetsTagController::class, 'ValidateQR'])->name('AssetsTag.ValidateQR');
        Route::post('ValidateQR', [AssetsTagController::class, 'ValidateQR'])->name('AssetsTag.ValidateQR_post');
        Route::get('Download', [AssetsTagController::class, 'Download'])->name('AssetsTag.Download');
        Route::get('createsingle', [AssetsTagController::class, 'createsingle'])->name('AssetsTag.createsingle');
        Route::post('createsingle', [AssetsTagController::class, 'createsingle'])->name('AssetsTag.createsingle_post');
        Route::post('getTagList', [AssetsTagController::class, 'getTagList'])->name('Assetstag.getTagList');
    });


    Route::group(['prefix' => 'CoreSites', 'middleware' => ['auth']], function () {
        Route::get('index', [CoreSitesController::class, 'index'])->name('CoreSites.index');
        Route::post('index', [CoreSitesController::class, 'index'])->name('CoreSites.index_post');
        Route::get('create', [CoreSitesController::class, 'create'])->name('CoreSites.create');
        Route::post('store', [CoreSitesController::class, 'store'])->name('CoreSites.store');
        Route::post('/destroy/{id}', [CoreSitesController::class, 'destroy'])->name('CoreSites.destroy');
        Route::get('edit/{id}', [CoreSitesController::class, 'edit'])->name('CoreSites.edit');
        Route::post('edit/{id}', [CoreSitesController::class, 'edit'])->name('CoreSites.edit_post');
        Route::get('bulk_upload', [CoreSitesController::class, 'bulk_upload'])->name('CoreSites.bulk_upload');
        Route::post('bulk_upload', [CoreSitesController::class, 'bulk_upload'])->name('CoreSites.bulk_upload_post');
    });
    Route::group(['prefix' => 'btsSites', 'middleware' => ['auth']], function () {
        Route::get('index', [btsSitesController::class, 'index'])->name('btsSites.index');
        Route::post('index', [btsSitesController::class, 'index'])->name('btsSites.index_post');
        Route::get('create', [btsSitesController::class, 'create'])->name('btsSites.create');
        Route::post('store', [btsSitesController::class, 'store'])->name('btsSites.store');
        Route::post('/destroy/{id}', [btsSitesController::class, 'destroy'])->name('btsSites.destroy');
        Route::get('edit/{id}', [btsSitesController::class, 'edit'])->name('btsSites.edit');
        Route::post('edit/{id}', [btsSitesController::class, 'edit'])->name('btsSites.edit_post');
        Route::get('bulk_upload', [btsSitesController::class, 'bulk_upload'])->name('btsSites.bulk_upload');
        Route::post('bulk_upload', [btsSitesController::class, 'bulk_upload'])->name('btsSites.bulk_upload_post');
        Route::get('bulk_upload_cell_site', [btsSitesController::class, 'bulk_upload_cell_site'])->name('btsSites.bulk_upload_cell_site');
        Route::post('bulk_upload_cell_site', [btsSitesController::class, 'bulk_upload_cell_site'])->name('btsSites.bulk_upload_cell_site_post');
        Route::get('cell_site_index', [btsSitesController::class, 'cell_site_index'])->name('btsSites.cell_site_index');
        Route::post('cell_site_index', [btsSitesController::class, 'cell_site_index'])->name('btsSites.cell_site_index_post');
        Route::get('cell_site_edit/{id}', [btsSitesController::class, 'cell_site_edit'])->name('btsSites.cell_site_edit');
        Route::post('cell_site_edit/{id}', [btsSitesController::class, 'cell_site_edit'])->name('btsSites.cell_site_edit_post');
        Route::get('cell_site_create', [btsSitesController::class, 'cell_site_create'])->name('btsSites.cell_site_create');
        Route::post('cell_site_store', [btsSitesController::class, 'cell_site_store'])->name('btsSites.cell_site_store');
        Route::post('cell_site_destroy/{id}', [btsSitesController::class, 'cell_site_destroy'])->name('btsSites.cell_site_destroy');
    });
    Route::group(['prefix' => 'MwTxSites', 'middleware' => ['auth']], function () {
        Route::get('index', [MwTxSitesController::class, 'index'])->name('MwTxSites.index');
        Route::post('index', [MwTxSitesController::class, 'index'])->name('MwTxSites.index_post');
        Route::get('create', [MwTxSitesController::class, 'create'])->name('MwTxSites.create');
        Route::post('store', [MwTxSitesController::class, 'store'])->name('MwTxSites.store');
        Route::post('/destroy/{id}', [MwTxSitesController::class, 'destroy'])->name('MwTxSites.destroy');
        Route::get('edit/{id}', [MwTxSitesController::class, 'edit'])->name('MwTxSites.edit');
        Route::post('edit/{id}', [MwTxSitesController::class, 'edit'])->name('MwTxSites.edit_post');
        Route::get('bulk_upload', [MwTxSitesController::class, 'bulk_upload'])->name('MwTxSites.bulk_upload');
        Route::post('bulk_upload', [MwTxSitesController::class, 'bulk_upload'])->name('MwTxSites.bulk_upload_post');
    });

    Route::group(['prefix' => 'OfTxSites', 'middleware' => ['auth']], function () {
        Route::get('index', [OfTxSitesController::class, 'index'])->name('OfTxSites.index');
        Route::post('index', [OfTxSitesController::class, 'index'])->name('OfTxSites.index_post');
        Route::get('create', [OfTxSitesController::class, 'create'])->name('OfTxSites.create');
        Route::post('store', [OfTxSitesController::class, 'store'])->name('OfTxSites.store');
        Route::post('/destroy/{id}', [OfTxSitesController::class, 'destroy'])->name('OfTxSites.destroy');
        Route::get('edit/{id}', [OfTxSitesController::class, 'edit'])->name('OfTxSites.edit');
        Route::post('edit/{id}', [OfTxSitesController::class, 'edit'])->name('OfTxSites.edit_post');
        Route::get('bulk_upload', [OfTxSitesController::class, 'bulk_upload'])->name('OfTxSites.bulk_upload');
        Route::post('bulk_upload', [OfTxSitesController::class, 'bulk_upload'])->name('OfTxSites.bulk_upload_post');
    });
    Route::group(['prefix' => 'BscRncSites', 'middleware' => ['auth']], function () {
        Route::get('index', [BscRncSitesController::class, 'index'])->name('BscRncSites.index');
        Route::post('index', [BscRncSitesController::class, 'index'])->name('BscRncSites.index_post');
        Route::get('create', [BscRncSitesController::class, 'create'])->name('BscRncSites.create');
        Route::post('store', [BscRncSitesController::class, 'store'])->name('BscRncSites.store');
        Route::post('/destroy/{id}', [BscRncSitesController::class, 'destroy'])->name('BscRncSites.destroy');
        Route::get('edit/{id}', [BscRncSitesController::class, 'edit'])->name('BscRncSites.edit');
        Route::post('edit/{id}', [BscRncSitesController::class, 'edit'])->name('BscRncSites.edit_post');
        Route::get('bulk_upload', [BscRncSitesController::class, 'bulk_upload'])->name('BscRncSites.bulk_upload');
        Route::post('bulk_upload', [BscRncSitesController::class, 'bulk_upload'])->name('BscRncSites.bulk_upload_post');
    });
    Route::group(['prefix' => 'planingData', 'middleware' => ['auth']], function () {
        Route::get('index', [PlaningDataController::class, 'index'])->name('planingData.index');
        Route::post('index', [PlaningDataController::class, 'index'])->name('planingData.index_post');
    });
    Route::group(['prefix' => 'MoveOrder', 'middleware' => ['auth']], function () {
        Route::post('getPlanningData', [MoveOrderController::class, 'getPlanningData'])->name('MoveOrder.getPlanningData');
        Route::post('getPlanningDataforMove', [MoveOrderController::class, 'getPlanningDataforMove'])->name('MoveOrder.getPlanningDataforMove');
        Route::get('index', [MoveOrderController::class, 'index'])->name('MoveOrder.index');
        Route::get('create', [MoveOrderController::class, 'create'])->name('MoveOrder.create');
        Route::post('save', [MoveOrderController::class, 'save'])->name('MoveOrder.save');
        Route::post('validateSerialNumbers', [MoveOrderController::class, 'validateSerialNumbers'])->name('MoveOrder.validateSerialNumbers');
        Route::post('saveOrder', [MoveOrderController::class, 'saveOrder'])->name('MoveOrder.saveOrder');       
        Route::get('edit/{id}', [MoveOrderController::class, 'edit'])->name('MoveOrder.edit');
        Route::get('show/{id}', [MoveOrderController::class, 'show'])->name('MoveOrder.show');
        Route::post('getDataList', [MoveOrderController::class, 'getDataList'])->name('MoveOrder.getDataList');   
        Route::get('planningdataOld', [MoveOrderController::class, 'planningdataOld'])->name('MoveOrder.planningdataOld');
        Route::post('getPlanningDataOld', [MoveOrderController::class, 'getPlanningDataOld'])->name('MoveOrder.getPlanningDataOld');
    });
    Route::group(['prefix' => 'UserRoles', 'middleware' => ['auth']], function () {
        Route::get('index', [UserRoleController::class, 'index'])->name('UserRole.index');
        Route::post('index', [UserRoleController::class, 'index'])->name('UserRole.index_post');
        Route::post('create', [UserRoleController::class, 'create'])->name('UserRole.create');
        Route::post('show', [UserRoleController::class, 'show'])->name('UserRole.show');
        Route::post('store', [UserRoleController::class, 'store'])->name('UserRole.store');
        Route::post('destroy/{id}', [UserRoleController::class, 'destroy'])->name('UserRole.destroy');
        Route::get('edit/{id}', [UserRoleController::class, 'edit'])->name('UserRole.edit');
        Route::post('edit/{id}', [UserRoleController::class, 'edit'])->name('UserRole.edit_post');
        Route::get('RoleItemsPopulate/', [UserRoleController::class, 'RoleItemsPopulate'])->name('UserRole.RoleItemsPopulate');
        Route::get('userList/', [UserRoleController::class, 'userList'])->name('UserRole.userList');
        Route::get('show-role/{id}', [UserRoleController::class, 'showRole'])->name('UserRole.showRole');
        Route::post('show-role/{id}', [UserRoleController::class, 'showRole'])->name('UserRole.showRole_post');
        Route::post('destroy/rolesItem/{id}', [UserRoleController::class, 'rolesItem'])->name('UserRole.destroyRolesItem');
        Route::post('destroyRoleUserspecific/{id}', [UserRoleController::class, 'destroyRoleUserspecific'])->name('UserRole.destroyRoleUserspecific');
        Route::get('showRoleDetail', [UserRoleController::class, 'showRoleDetail'])->name('UserRole.RoleDetail');
    });

    Route::group(['prefix' => 'UserHistory', 'middleware' => ['auth']], function () {
        Route::get('index', [UserHistoryController::class, 'index'])->name('UserHistory.index');
        Route::post('index', [UserHistoryController::class, 'index'])->name('UserHistory.index_post');
    });

    Route::group(['prefix' => 'Colocated', 'middleware' => ['auth']], function () {
        Route::get('index', [ColocatedController::class, 'index'])->name('Colocated.index');
        Route::post('index', [ColocatedController::class, 'index'])->name('Colocated.index_post');
    });
});
Route::get('/forgot-password', function () {
    return view('auth.passwords.email');
})->middleware('guest')->name('password.request');
Route::get('/password/reset', function (Request $request) {
    return view('auth.passwords.reset', $request->all());
})->middleware('guest')->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'forgot']);
Route::post('password/reset', [ForgotPasswordController::class, 'reset']);
Route::get('getumssites/', function () {
    Artisan::call('command:get_ums_sites');
});
Route::get('sftp_file_view/', function (Request $request) {
    $validated = $request->validate(['filename' => 'required|min:4']);
    $contains = Str::contains($request->filename, '/');
    if ($contains) {
        return Storage::disk('sftp')->get($request->filename);
    }
})->name('sftp_file_view');
Route::get('sftp_file_download/', function (Request $request) {
    $validated = $request->validate(['filename' => 'required|min:4']);
    $Attachment = Attachment::where('file_name', $request->filename)->withTrashed()->firstOrFail();
    if (in_array(strtolower($Attachment->file_extension), ['jpg', 'png', 'jpeg', 'jpg']) and ($request->is_view)) {
        return view('Asset.show_attachments_single_image', array('Attachment' => $Attachment));
    }
    $title = isset($Attachment->title) ? $Attachment->title : '';
    if (Storage::disk('sftp')->exists($request->filename)) {
        return Storage::disk('sftp')->download($request->filename, $title);
    } else {
        abort(404);
    }
})->name('sftp_file_download');

/* Route::get('/jsonfile', function () {

    $array = '[{
          "asset_qr_code": "3001001MB20",
          "asset_configuration_attributes": "[[{\"title\":\"Cell Id\",\"value\":\"\"},{\"title\":\"E-Tilt\",\"value\":\"\"},{\"title\":\"Azimuth\",\"value\":\"90.80\"},{\"title\":\"Altitude\",\"value\":\"\"},{\"title\":\"M-Tilt\",\"value\":\"89.05\"}]]"
       }]';
 
     $json_data = json_decode($array, true);
     $j = 0;
     $i = 0;
     $outPutArray = array();
     $qr_code_Array = array(
       '3001001MB20'
     );
     $tempArr = array_unique(array_column($json_data, 'asset_qr_code'));
     $json_data111 = (array_intersect_key($json_data, $tempArr));
     foreach ($json_data111 as $singleData) {
         if (!empty($singleData['asset_configuration_attributes'])) {
             if(in_array($singleData['asset_qr_code'],array_unique($qr_code_Array))){
                 $j++;
                 $status = DB::table('assets')
                     ->where('asset_qr_code', $singleData['asset_qr_code'])  // find your user by their email
                     ->update(array('asset_configuration_attributes' => json_decode($singleData['asset_configuration_attributes'])));  // update the record in the DB.
                 if ($status) {
                     $i++;
                     echo 'update: '.$singleData['asset_qr_code'].'<br/>';
                 }else{
                     echo 'not update: '.$singleData['asset_qr_code'].'<br/>';
                 }
             }
         }
     }
     echo 'number record'.$j.'<br/>';
     echo 'number update record'.$i.'<br/>';
})->name('jsonfile'); */

/**
 * CRONJOBS URLS FOR TESTING PURPOSE
**/
Route::get('/get_and_confirm_receiving_at_warehouse' , function(){
    Artisan::call('command:get_and_confirm_receiving_at_warehouse');
    return 'Cronjob Executed';
});

Route::get('/get_and_update_warehouse_list' , function(){
    Artisan::call('command:get_and_update_warehouse_list');
    return 'Cronjob Executed';
});

Route::get('/update_assets_missing_serial_number' , function(){
    Artisan::call('command:update_assets_missing_serial_number');
    return 'Cronjob Executed';
});