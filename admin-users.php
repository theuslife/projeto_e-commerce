<?php
//Usando a classe Page para gerar o template completo HTML (Header, index, footer)
use \Hcode\PageAdmin;

//Classe de usuário
use \Hcode\Model\User;


//Users screen
$app->get("/admin/users", function(){

	//Check user login
	User::verifyLogin();

	//All list of users selected from the database
	$users = User::listAll();

	//Pesquisa
	$search = (isset($_GET['search']))? $_GET['search'] : ""; 

	//Página atual
	$page = (isset($_GET['page'])) ? (int) $_GET['page'] : 1;

	if($search != '')
	{
		$pagination = User::getPageSearch($search, $page, 10);
	}
	else 
	{
		$pagination = User::getPage($page, 10);
	}

	$pages = [];

	for ($i=0; $i < $pagination['pages'] ; $i++)
	{ 
		array_push($pages, [
			'href'=>'/admin/users?' . http_build_query([
				'page'=>$i+1,
				'search'=>$search
			]),
			'text'=>$i+1
		]);
	}

	//Tpl
	$page = new PageAdmin();
	
	//Draw the users page
	$page->setTpl("users", array(
		"users"=>$pagination['data'],
		"search"=>$search,
		"pages"=>$pages
	));

});

//Creation route
$app->get("/admin/users/create", function(){

	User::verifyLogin();
	
	$page = new PageAdmin();
	
	$page->setTpl("users-create");

});

//Users delete
$app->get("/admin/users/:iduser/delete", function($iduser){
	
	User::verifyLogin();
	
	$user = new User();

	$user->get((int)$iduser);

	$user->delete();
	
	header("Location: /admin/users");
	exit;

});

//Users list
$app->get("/admin/users/:iduser", function($iduser){

	User::verifyLogin();
	
	$user = new User();

	$user->get((int)$iduser);

	$page = new PageAdmin();

	$page->setTpl("users-update", array(
		"user"=>$user->getValues()
	));

});

//This route will send a post to another route
$app->post("/admin/users/create", function(){
	
	User::verifyLogin();
	
	$user = new User();
	
	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
	
	$user->setData($_POST);
	
	$user->save();
	
	header("Location: /admin/users");
	
	exit;

});

//This route will send a post to another route too
$app->post("/admin/users/:iduser", function($iduser){
	
	User::verifyLogin();

	$user = new User();
	
	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

	$user->get((int)$iduser);

	$user->setData($_POST);

	$user->update();

	header("Location: /admin/users");

	exit;

});

$app->get("/admin/users/:iduser/password", function($iduser){

	User::verifyLogin();

	$user = new User();

	$user->get((int) $iduser);

	$page = new PageAdmin();

	$page->setTpl("users-password", [
		'user'=>$user->getValues(),
		'msgError'=>User::getErrorRegister(),
		'msgSuccess'=>User::getSucessRegister()
	]);

});

$app->post("/admin/users/:iduser/password", function($iduser){

	User::verifyLogin();

	$user = new User();

	$user->get((int) $iduser);

	//Validações
	if(!isset($_POST['despassword']) || $_POST['despassword'] === '')
	{
		User::setErrorRegister('Digite uma senha válida');
		Header("Location: /admin/users/" . $iduser . "/password");
		exit;
	}

	if(password_verify($_POST['despassword'], $user->getdespassword()))
	{
		User::setErrorRegister('Senha digitada é a mesma que a atual');
		Header("Location: /admin/users/" . $iduser . "/password");
		exit;
	}

	if($_POST['despassword'] !== $_POST['despassword-confirm'])
	{
		User::setErrorRegister('Senhas não se confirmam');
		Header("Location: /admin/users/" . $iduser . "/password");
		exit;
	}

	$user->setdespassword($_POST['despassword']);

	$user->update();

	User::setSucessRegister('Senha alterada com sucesso');

	Header("Location: /admin/users/" . $iduser . "/password");
	exit;

});