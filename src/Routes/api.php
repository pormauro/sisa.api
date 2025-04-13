<?php
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Middleware\CheckUserBlockedMiddleware;
use App\Middleware\PermissionsMiddleware;
use App\Controllers\PermissionsController;
use App\Controllers\FilesController;
use App\Controllers\ProfileController;
use App\Controllers\AuthController;
use App\Controllers\FoldersController;

$authController = new AuthController();

// Rutas públicas
$app->post('/register', function (Request $request, Response $response, $args) use ($authController) {
    $data = json_decode($request->getBody()->getContents(), true);
    $result = $authController->register($data);
    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/login', function (Request $request, Response $response, $args) use ($authController) {
    $data = json_decode($request->getBody()->getContents(), true);
    $result = $authController->login($data);
    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/forgot_password', function (Request $request, Response $response, $args) use ($authController) {
    $data = json_decode($request->getBody()->getContents(), true);
    $result = $authController->forgotPassword($data);
    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/reset_password', function (Request $request, Response $response, $args) use ($authController) {
    if (!$request->hasHeader('Authorization')) {
        $data = ['error' => 'Falta header Authorization'];
        $response->getBody()->write(json_encode($data));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
    $resetToken = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
    $data = json_decode($request->getBody()->getContents(), true);
    $newPassword = $data['new_password'] ?? null;
    $confirmPassword = $data['confirm_password'] ?? null;
    if (!$newPassword || !$confirmPassword) {
        $data = ['error' => 'Faltan new_password y/o confirm_password'];
        $response->getBody()->write(json_encode($data));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    if ($newPassword !== $confirmPassword) {
        $data = ['error' => 'Las contraseñas no coinciden'];
        $response->getBody()->write(json_encode($data));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    $result = $authController->resetPassword($resetToken, $newPassword);
    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

//-----------------------------------------------------------------------------------------------
//-----------------------------------------------------------------------------------------------
//-----------------------------------------------------------------------------------------------

// Perfil del usuario (permiso: 'getProfile')
$app->get('/profile', function (Request $request, Response $response, $args) {
    $controller = new ProfileController();
    return $controller->getProfile($request, $response, $args);
})
   // ->add(new PermissionsMiddleware('getProfile'))
    ->add(new CheckUserBlockedMiddleware());

$app->get('/', function (Request $req, Response $res) {
    $res->getBody()->write(json_encode(['message' => 'Bienvenido a la API']));
    return $res->withHeader('Content-Type', 'application/json');
});





// Subir archivos (permiso: 'uploadFile')
$app->post('/files', function (Request $request, Response $response, $args) {
    $controller = new FilesController();
    return $controller->upload($request, $response, $args);
})
    ->add(new PermissionsMiddleware('uploadFile'))
    ->add(new CheckUserBlockedMiddleware());

// Descargar archivos (permiso: 'downloadFile')
// Ahora el file_id se obtiene desde la URL, conforme a la definición de la ruta
$app->get('/files/{file_id}', function (Request $request, Response $response, $args) {
    $controller = new FilesController();
    return $controller->download($request, $response, $args);
})
    ->add(new PermissionsMiddleware('downloadFile'))
    ->add(new CheckUserBlockedMiddleware());
//--------------------------------------------------------------------------------------------------------------------
//----------------------------------Super administrador user_id=1-----------------------------------------------------
//--------------------------------------------------------------------------------------------------------------------

// Ruta para listar solo los permisos globales (user_id = NULL)
$app->get('/permissions/global', [PermissionsController::class, 'listGlobalPermissions'])
    ->add(new PermissionsMiddleware('listGlobalPermissions'))
    ->add(new CheckUserBlockedMiddleware());

// Ruta para listar permisos filtrados por un usuario específico
$app->get('/permissions/user/{user_id}', [PermissionsController::class, 'listPermissionsByUser'])
    ->add(new PermissionsMiddleware('listPermissionsByUser'))
    ->add(new CheckUserBlockedMiddleware());
    
// Ruta para listar todos los permisos
$app->get('/permissions', [PermissionsController::class, 'listPermissions'])
    ->add(new PermissionsMiddleware('listPermissions'))
    ->add(new CheckUserBlockedMiddleware());
    
// Ruta para agregar permisos a los usuarios (protección: permiso 'addPermission')
$app->post('/permissions', [PermissionsController::class, 'addPermission'])
    ->add(new PermissionsMiddleware('addPermission'))
    ->add(new CheckUserBlockedMiddleware());
    
// Ruta para eliminar un permiso (por ID)
$app->delete('/permissions/{id}', [PermissionsController::class, 'deletePermission'])
    ->add(new PermissionsMiddleware('deletePermission'))
    ->add(new CheckUserBlockedMiddleware());
    
// Suponiendo que usas un router tipo Slim o similar:
$app->get('/profiles', [\App\Controllers\ProfilesListController::class, 'listAllProfiles'])
    ->add(new PermissionsMiddleware('listAllProfiles'))
    ->add(new CheckUserBlockedMiddleware());
//--------------------------------------------------------------------------------------------------------------------
//--------------------------------------------------------------------------------------------------------------------
//--------------------------------------------------------------------------------------------------------------------
// Rutas protegidas con permisos:
//--------------------------------------------------------------------------------------------------------------------
//--------------------------------------------------------------------------------------------------------------------
//--------------------------------------------------------------------------------------------------------------------

// Endpoints para User Profile
$app->get('/user_profile', [\App\Controllers\UserProfileController::class, 'myProfile']) 
    ->add(new CheckUserBlockedMiddleware()); 
$app->get('/user_profile/list', [\App\Controllers\UserProfileController::class, 'listProfiles'])
    ->add(new PermissionsMiddleware('listUserProfiles'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/user_profile/{id}', [\App\Controllers\UserProfileController::class, 'getProfile'])
    ->add(new PermissionsMiddleware('getUserProfile'))
    ->add(new CheckUserBlockedMiddleware());
$app->post('/user_profile', [\App\Controllers\UserProfileController::class, 'addProfile'])
    ->add(new PermissionsMiddleware('addUserProfile'))
    ->add(new CheckUserBlockedMiddleware());
$app->put('/user_profile', [\App\Controllers\UserProfileController::class, 'updateProfile'])
    ->add(new CheckUserBlockedMiddleware());
$app->delete('/user_profile', [\App\Controllers\UserProfileController::class, 'deleteProfile'])
    ->add(new CheckUserBlockedMiddleware());

// Endpoints para User Configurations
$app->get('/user_configurations', [\App\Controllers\UserConfigurationsController::class, 'myConfigurations'])
    ->add(new CheckUserBlockedMiddleware());
$app->get('/user_configurations/list', [\App\Controllers\UserConfigurationsController::class, 'listConfigurations'])
    ->add(new PermissionsMiddleware('listUserConfigurations'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/user_configurations/{id}', [\App\Controllers\UserConfigurationsController::class, 'getConfiguration'])
    ->add(new PermissionsMiddleware('getUserConfigurations'))
    ->add(new CheckUserBlockedMiddleware());
$app->post('/user_configurations', [\App\Controllers\UserConfigurationsController::class, 'addConfiguration'])
    ->add(new PermissionsMiddleware('addUserConfigurations'))
    ->add(new CheckUserBlockedMiddleware());
$app->put('/user_configurations', [\App\Controllers\UserConfigurationsController::class, 'updateConfiguration'])
    ->add(new CheckUserBlockedMiddleware());
$app->delete('/user_configurations', [\App\Controllers\UserConfigurationsController::class, 'deleteConfiguration'])
    ->add(new CheckUserBlockedMiddleware());

// Endpoints para Clients
$app->get('/clients/{id}', [\App\Controllers\ClientsController::class, 'getClient'])
    ->add(new PermissionsMiddleware('getClient'))
    ->add(new CheckUserBlockedMiddleware());
$app->post('/clients', [\App\Controllers\ClientsController::class, 'addClient'])
    ->add(new PermissionsMiddleware('addClient'))
    ->add(new CheckUserBlockedMiddleware());
$app->put('/clients/{id}', [\App\Controllers\ClientsController::class, 'updateClient'])
    ->add(new PermissionsMiddleware('updateClient'))
    ->add(new CheckUserBlockedMiddleware());
$app->delete('/clients/{id}', [\App\Controllers\ClientsController::class, 'deleteClient'])
    ->add(new PermissionsMiddleware('deleteClient'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/clients', [\App\Controllers\ClientsController::class, 'listClients'])
    ->add(new PermissionsMiddleware('listClients'))
    ->add(new CheckUserBlockedMiddleware());

// Endpoints para Folders
$app->get('/folders', [FoldersController::class, 'listFolders'])
    ->add(new PermissionsMiddleware('listFolders'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/folders/{id}', [FoldersController::class, 'getFolder'])
    ->add(new PermissionsMiddleware('getFolder'))
    ->add(new CheckUserBlockedMiddleware());
$app->post('/folders', [FoldersController::class, 'addFolder'])
    ->add(new PermissionsMiddleware('addFolder'))
    ->add(new CheckUserBlockedMiddleware());
$app->put('/folders/{id}', [FoldersController::class, 'updateFolder'])
    ->add(new PermissionsMiddleware('updateFolder'))
    ->add(new CheckUserBlockedMiddleware());
$app->delete('/folders/{id}', [FoldersController::class, 'deleteFolder'])
    ->add(new PermissionsMiddleware('deleteFolder'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/folders/{id}/history', [FoldersController::class, 'listFolderHistory'])
    ->add(new PermissionsMiddleware('listFolderHistory'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/folders/client/{client_id}', [FoldersController::class, 'listFoldersByClient'])
    ->add(new PermissionsMiddleware('listFoldersByClient'))
    ->add(new CheckUserBlockedMiddleware());

use App\Controllers\CashBoxesController;

// Endpoints para Cash Boxes
$app->get('/cash_boxes', [CashBoxesController::class, 'listCashBoxes'])
    ->add(new PermissionsMiddleware('listCashBoxes'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/cash_boxes/{id}', [CashBoxesController::class, 'getCashBox'])
    ->add(new PermissionsMiddleware('getCashBox'))
    ->add(new CheckUserBlockedMiddleware());
$app->post('/cash_boxes', [CashBoxesController::class, 'addCashBox'])
    ->add(new PermissionsMiddleware('addCashBox'))
    ->add(new CheckUserBlockedMiddleware());
$app->put('/cash_boxes/{id}', [CashBoxesController::class, 'updateCashBox'])
    ->add(new PermissionsMiddleware('updateCashBox'))
    ->add(new CheckUserBlockedMiddleware());
$app->delete('/cash_boxes/{id}', [CashBoxesController::class, 'deleteCashBox'])
    ->add(new PermissionsMiddleware('deleteCashBox'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/cash_boxes/{id}/history', [CashBoxesController::class, 'listCashBoxHistory'])
    ->add(new PermissionsMiddleware('listCashBoxHistory'))
    ->add(new CheckUserBlockedMiddleware());

use App\Controllers\SalesController;

// Endpoints para Sales
$app->get('/sales', [SalesController::class, 'listSales'])
    ->add(new PermissionsMiddleware('listSales'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/sales/{id}', [SalesController::class, 'getSale'])
    ->add(new PermissionsMiddleware('getSale'))
    ->add(new CheckUserBlockedMiddleware());
$app->post('/sales', [SalesController::class, 'addSale'])
    ->add(new PermissionsMiddleware('addSale'))
    ->add(new CheckUserBlockedMiddleware());
$app->put('/sales/{id}', [SalesController::class, 'updateSale'])
    ->add(new PermissionsMiddleware('updateSale'))
    ->add(new CheckUserBlockedMiddleware());
$app->delete('/sales/{id}', [SalesController::class, 'deleteSale'])
    ->add(new PermissionsMiddleware('deleteSale'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/sales/{id}/history', [SalesController::class, 'listSaleHistory'])
    ->add(new PermissionsMiddleware('listSaleHistory'))
    ->add(new CheckUserBlockedMiddleware());

use App\Controllers\ProductsServicesController;

$app->get('/products_services', [ProductsServicesController::class, 'listProductsServices'])
    ->add(new PermissionsMiddleware('listProductsServices'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/products_services/{id}', [ProductsServicesController::class, 'getProductService'])
    ->add(new PermissionsMiddleware('getProductService'))
    ->add(new CheckUserBlockedMiddleware());
$app->post('/products_services', [ProductsServicesController::class, 'addProductService'])
    ->add(new PermissionsMiddleware('addProductService'))
    ->add(new CheckUserBlockedMiddleware());
$app->put('/products_services/{id}', [ProductsServicesController::class, 'updateProductService'])
    ->add(new PermissionsMiddleware('updateProductService'))
    ->add(new CheckUserBlockedMiddleware());
$app->delete('/products_services/{id}', [ProductsServicesController::class, 'deleteProductService'])
    ->add(new PermissionsMiddleware('deleteProductService'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/products_services/{id}/history', [ProductsServicesController::class, 'listProductServiceHistory'])
    ->add(new PermissionsMiddleware('listProductServiceHistory'))
    ->add(new CheckUserBlockedMiddleware());

use App\Controllers\JobsController;

$app->get('/jobs', [JobsController::class, 'listJobs'])
    ->add(new PermissionsMiddleware('listJobs'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/jobs/{id}', [JobsController::class, 'getJob'])
    ->add(new PermissionsMiddleware('getJob'))
    ->add(new CheckUserBlockedMiddleware());
$app->post('/jobs', [JobsController::class, 'addJob'])
    ->add(new PermissionsMiddleware('addJob'))
    ->add(new CheckUserBlockedMiddleware());
$app->put('/jobs/{id}', [JobsController::class, 'updateJob'])
    ->add(new PermissionsMiddleware('updateJob'))
    ->add(new CheckUserBlockedMiddleware());
$app->delete('/jobs/{id}', [JobsController::class, 'deleteJob'])
    ->add(new PermissionsMiddleware('deleteJob'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/jobs/{id}/history', [JobsController::class, 'listJobHistory'])
    ->add(new PermissionsMiddleware('listJobHistory'))
    ->add(new CheckUserBlockedMiddleware());

use App\Controllers\ExpensesController;

$app->get('/expenses', [ExpensesController::class, 'listExpenses'])
    ->add(new PermissionsMiddleware('listExpenses'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/expenses/{id}', [ExpensesController::class, 'getExpense'])
    ->add(new PermissionsMiddleware('getExpense'))
    ->add(new CheckUserBlockedMiddleware());
$app->post('/expenses', [ExpensesController::class, 'addExpense'])
    ->add(new PermissionsMiddleware('addExpense'))
    ->add(new CheckUserBlockedMiddleware());
$app->put('/expenses/{id}', [ExpensesController::class, 'updateExpense'])
    ->add(new PermissionsMiddleware('updateExpense'))
    ->add(new CheckUserBlockedMiddleware());
$app->delete('/expenses/{id}', [ExpensesController::class, 'deleteExpense'])
    ->add(new PermissionsMiddleware('deleteExpense'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/expenses/{id}/history', [ExpensesController::class, 'listExpenseHistory'])
    ->add(new PermissionsMiddleware('listExpenseHistory'))
    ->add(new CheckUserBlockedMiddleware());

use App\Controllers\AppointmentsController;

$app->get('/appointments', [AppointmentsController::class, 'listAppointments'])
    ->add(new PermissionsMiddleware('listAppointments'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/appointments/{id}', [AppointmentsController::class, 'getAppointment'])
    ->add(new PermissionsMiddleware('getAppointment'))
    ->add(new CheckUserBlockedMiddleware());
$app->post('/appointments', [AppointmentsController::class, 'addAppointment'])
    ->add(new PermissionsMiddleware('addAppointment'))
    ->add(new CheckUserBlockedMiddleware());
$app->put('/appointments/{id}', [AppointmentsController::class, 'updateAppointment'])
    ->add(new PermissionsMiddleware('updateAppointment'))
    ->add(new CheckUserBlockedMiddleware());
$app->delete('/appointments/{id}', [AppointmentsController::class, 'deleteAppointment'])
    ->add(new PermissionsMiddleware('deleteAppointment'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/appointments/{id}/history', [AppointmentsController::class, 'listAppointmentHistory'])
    ->add(new PermissionsMiddleware('listAppointmentHistory'))
    ->add(new CheckUserBlockedMiddleware());

use App\Controllers\AccountingClosingsController;

$app->get('/accounting_closings', [AccountingClosingsController::class, 'listClosings'])
    ->add(new PermissionsMiddleware('listClosings'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/accounting_closings/{id}', [AccountingClosingsController::class, 'getClosing'])
    ->add(new PermissionsMiddleware('getClosing'))
    ->add(new CheckUserBlockedMiddleware());
$app->post('/accounting_closings', [AccountingClosingsController::class, 'addClosing'])
    ->add(new PermissionsMiddleware('addClosing'))
    ->add(new CheckUserBlockedMiddleware());
$app->put('/accounting_closings/{id}', [AccountingClosingsController::class, 'updateClosing'])
    ->add(new PermissionsMiddleware('updateClosing'))
    ->add(new CheckUserBlockedMiddleware());
$app->delete('/accounting_closings/{id}', [AccountingClosingsController::class, 'deleteClosing'])
    ->add(new PermissionsMiddleware('deleteClosing'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/accounting_closings/{id}/history', [AccountingClosingsController::class, 'listClosingHistory'])
    ->add(new PermissionsMiddleware('listClosingHistory'))
    ->add(new CheckUserBlockedMiddleware());
    
    
use App\Controllers\StatusController;

$app->get('/statuses', [StatusController::class, 'listStatuses'])
    ->add(new PermissionsMiddleware('listStatuses'))
    ->add(new CheckUserBlockedMiddleware());
$app->post('/statuses', [StatusController::class, 'addStatus'])
    ->add(new PermissionsMiddleware('addStatus'))
    ->add(new CheckUserBlockedMiddleware());
$app->put('/statuses/reorder', [StatusController::class, 'reorderStatuses'])
    ->add(new PermissionsMiddleware('reorderStatuses'))
    ->add(new CheckUserBlockedMiddleware());
$app->get('/statuses/{id:\d+}', [StatusController::class, 'getStatus'])
    ->add(new PermissionsMiddleware('getStatus'))
    ->add(new CheckUserBlockedMiddleware());
$app->put('/statuses/{id:\d+}', [StatusController::class, 'updateStatus'])
    ->add(new PermissionsMiddleware('updateStatus'))
    ->add(new CheckUserBlockedMiddleware());
$app->delete('/statuses/{id:\d+}', [StatusController::class, 'deleteStatus'])
    ->add(new PermissionsMiddleware('deleteStatus'))
    ->add(new CheckUserBlockedMiddleware());



// Ruta por defecto para manejar "Not found"
$app->any('/{routes:.+}', function (Request $request, Response $response) {
    $data = ['error' => 'Not found'];
    $response->getBody()->write(json_encode($data));
    return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
});
