<?php

namespace Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model
{

    const SESSION = "User";
    const SESSION_ERROR = "UserError";
    const SECRET = "HcodePhp7_Secret";
    const SESSION_REGISTER_SUCESS = 'UserSucessRegister';
    const SESSION_REGISTER_ERROR = "UserErrorRegister";

    public static function getFromSession()
    {   
        
        $user = new User();

        if (isset($_SESSION[user::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0)
        {
            $user->setData($_SESSION[User::SESSION]);
        }

        return $user;

    }


    public static function checkLogin($inadmin = true)
    {

        if (!isset($_SESSION[user::SESSION])  || !$_SESSION[User::SESSION] || !(int)$_SESSION[User::SESSION]["iduser"] > 0)
        {
            return false;
        }
        else 
        {
            if($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true)
            {
                return true;
            } 
            else if ($inadmin === false) 
            {
                return true;
            } 
            else 
            {
                return false;
            }
        }

    }

    //Login
    public static function login($login, $password)
    {

        $sql = new Sql();
        
        $results = $sql->select("SELECT * FROM tb_users a 
        INNER JOIN tb_persons b
        WHERE a.idperson = b.idperson
        AND a.deslogin = :LOGINN", array(
            ":LOGINN"=>$login
        ));
        
        if(count($results) === 0 )
        {
            throw new \Exception("Usuário inexistente ou senha inválida", 1);
        }
        
        $data = $results[0];
        
        /*
         //Configurando o Login do usuário
        if($data["despassword"] == $password)
        {
            $user = new User();
            $user->setData($data);
    
            //Sessão para pegar os dados de login do usuário
            $_SESSION[User::SESSION] = $user->getValues();
            return $user;

        } 
        else 
        {
            throw new \Exception("Usuário inexistente ou senha inválida", 1);
        }
        */

        
            if(password_verify($password, $data["despassword"]))
            {
                $user = new User();
                $user->setData($data);
        
                //Sessão para pegar os dados de login do usuário
                $_SESSION[User::SESSION] = $user->getValues();
                return $user;
            } else 
            {
                throw new \Exception("Usuário inexistente ou senha inválida", 1);
            }
     

    }

    //Checks user login
    public static function verifyLogin($inadmin = true)
    {
        if(!User::checkLogin($inadmin))
        {
            if($inadmin)
            {
                header("Location: /admin/login");
                exit;
            } 
            else 
            {
                header("Location: /login");
                exit;
            }
        }
    }

    //User logout
    public static function logout()
    {
        $_SESSION[User::SESSION] = NULL;
    }

    //List all users
    public static function listAll()
    {

        $sql = new Sql();
        
        //Command "Join" is used in this line below
        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");

    }

    //Save the new user
    public function save()
    {
        $sql = new Sql;

        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>password_hash($this->getdespassword(), PASSWORD_DEFAULT),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));
        
        $this->setData($results[0]);

    }

    //Validação
    public static function validationRegister($data = array())
    {

            if(!User::validation($data['desperson']))
            {
                User::setErrorRegister("Digite o seu nome");
                Header("Location: /admin/register");
                exit;
            };
            if(!User::validation($data['deslogin']))
            {
                User::setErrorRegister("Digite o nome de usuário");
                Header("Location: /admin/register");
                exit;
            }
            if(!User::validation($data['nrphone']))
            {
                User::setErrorRegister("Digite o seu telefone");
                Header("Location: /admin/register");
                exit;
            }
            if(!User::validation($data['desemail']))
            {
                User::setErrorRegister("Digite o seu email");
                Header("Location: /admin/register");
                exit;
            }
            if(!User::validation($data['despassword']))
            {
                User::setErrorRegister("Digite a sua senha");
                Header("Location: /admin/register");
                exit;
            }


    }

    public static function validation($data)
    {

        if(!isset($data) || $data === '')
        {
            return false;
        } 
        else
        {
            return true;
        } 

    }

    //Function ''get'' id from user
    public function get($iduser)
    {
        
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
            ":iduser"=>$iduser
        ));

        $data = $results[0];

        $this->setData($data);

    }

    //User update
    public function update()
    {
        
        $sql = new Sql();

        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":iduser"=>$this->getiduser(),
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>password_hash($this->getdespassword(), PASSWORD_DEFAULT),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

        $this->setData($results[0]);

    }

    //User exclusion
    public function delete()
    {
        
        $sql = new Sql();

        $results = $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser"=>$this->getiduser()
        ));

    }

    //Get user email
    public static function getForgot($email, $inadmin = true)
    {

        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users a 
        INNER JOIN tb_persons b 
        USING(idperson) 
        WHERE b.desemail = :email;", array(
           ":email" => $email
        ));

        if(count($results) === 0)
        {
            throw new \Exception("Não foi possível recuperar a senha");
        } 
        else 
        {

            $data = $results[0];

            $recoveryResults = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                ":iduser"=>$data["iduser"],
                //Ip do usuário
                ":desip"=>$_SERVER["REMOTE_ADDR"]
            ));

            if(count($recoveryResults) === 0)
            {
                throw new \Exception("Não foi possível recuperar a senha");
            } 
            else 
            {
                
                $dataRecovery = $recoveryResults[0];

                $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
                
                $code = openssl_encrypt($dataRecovery["idrecovery"], 'aes-256-cbc', USER::SECRET, 0, $iv);

                $result = base64_encode($iv.$code);

                if($inadmin === true)
                {
                    $link = "http://www.e-commerce.com.br/admin/forgot/reset?code=$result";
                }
                else 
                {
                    $link = "http://www.e-commerce.com.br/forgot/reset?code=$result";
                }
                

                $mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir Senha da Hcode Store", "forgot", array(
                    "name"=>$data["desperson"],
                    "link"=>$link
                ));

                $mailer->send();
                return $data;

            }

        }

    }

    public static function validForgotDecrypt($result)
    {
        
        $result = base64_decode($result);
        
        //Encrypt and Decrypt
        $code = mb_substr($result, openssl_cipher_iv_length('aes-256-cbc'), null, '8bit');
        
        $iv = mb_substr($result, 0, openssl_cipher_iv_length('aes-256-cbc'), '8bit');

        $idrecovery = openssl_decrypt($code, 'aes-256-cbc', User::SECRET, 0, $iv);

        $sql = new Sql;

        $results = $sql->select("SELECT *
        FROM tb_userspasswordsrecoveries a
        INNER JOIN tb_users b USING(iduser)
        INNER JOIN tb_persons c USING(idperson)
        WHERE a.idrecovery = :idrecovery
        AND a.dtrecovery IS NULL
        AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();", array(
            ":idrecovery"=>$idrecovery
        ));

        if(count($results) === 0)
        {
            throw new \Exception("Não foi possível recuperar a senha");
        } 
        else 
        {
            return $results[0];
        }

    }

    public static function setForgotUsed($idrecovery)
    {
        
        $sql = new Sql;
        $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
            ":idrecovery"=>$idrecovery
        ));
    
    }

    public function setPassword($password)
    {
        $sql = new Sql();

        $sql->query("UPDATE tb_users SET despassword = :passwordd WHERE iduser = :iduser", array(
            ":passwordd"=>$password,
            ":iduser"=>$this->getiduser()
        ));
    }

    public static function setMsgError($msg)
    {

        $_SESSION[Cart::SESSION_ERROR] = $msg;

    }

    public static function getMsgError()
    {

        $msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR]:'';

        User::clearMsgError();

        return $msg;

    }

    public static function clearMsgError()
    {

        $_SESSION[Cart::SESSION_ERROR] = NULL;

    }


    public static function checkLoginExists($login)
    {
        $sql = new Sql();

        $result = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", array(
            ':deslogin'=>$login
        ));

        return (count($result) > 0);

    }

    //Sucess
    public static function setSucessRegister($msg)
    {

        $_SESSION[User::SESSION_REGISTER_SUCESS] = $msg;

    }

    public static function getSucessRegister()
    {

        $msg = (isset($_SESSION[User::SESSION_REGISTER_SUCESS])) ? $_SESSION[User::SESSION_REGISTER_SUCESS]:'';

        User::clearSucessRegister();

        return $msg;

    }

    public static function clearSucessRegister()
    {

        $_SESSION[User::SESSION_REGISTER_SUCESS] = NULL;

    }

    //Error
    public static function setErrorRegister($msg)
    {

        $_SESSION[User::SESSION_REGISTER_ERROR] = $msg;

    }

    public static function getErrorRegister()
    {

        $msg = (isset($_SESSION[User::SESSION_REGISTER_ERROR])) ? $_SESSION[User::SESSION_REGISTER_ERROR]:'';

        User::clearErrorRegister();

        return $msg;

    }

    public static function clearErrorRegister()
    {

        $_SESSION[User::SESSION_REGISTER_ERROR] = NULL;

    }

    public function getOrders()
    {

        $sql = new Sql();

        $result = $sql->select("SELECT *
        FROM tb_orders a
        INNER JOIN tb_ordersstatus b USING(idstatus)
        INNER JOIN tb_carts c USING(idcart)
        INNER JOIN tb_users d ON d.iduser = a.iduser
        INNER JOIN tb_addresses e USING(idaddress)
        INNER JOIN tb_persons f ON f.idperson = d.idperson
        WHERE a.iduser = :iduser", [
            ':iduser'=>$this->getiduser()
        ]);

        return $result;


    }

    public static function getPage($page = 1, $itemsPerPage = 10)
    {

        //Cálculo para colocarmos no LIMIT de nosso select no banco
        $start = ($page - 1) * $itemsPerPage;

        $sql = new Sql();

        //Perceba a variável no ínicio de nosso LIMIT
        $results = $sql->select("SELECT  SQL_CALC_FOUND_ROWS *
        FROM tb_users a 
        INNER JOIN tb_persons b USING(idperson) 
        ORDER BY b.desperson
        LIMIT $start, $itemsPerPage;");

        //Contagem de elementos do nosso resultado
        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

        return array(
            'data'=>$results,
            'total'=>(int)$resultTotal[0]['nrtotal'],
            'pages'=>ceil($resultTotal[0]['nrtotal'] / $itemsPerPage)
        );

    }

    public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
    {

        //Cálculo para colocarmos no LIMIT de nosso select no banco
        $start = ($page - 1) * $itemsPerPage;

        $sql = new Sql();

        //Perceba a variável no ínicio de nosso LIMIT
        $results = $sql->select("SELECT  SQL_CALC_FOUND_ROWS *
        FROM tb_users a 
        INNER JOIN tb_persons b USING(idperson) 
        WHERE b.desperson LIKE :search OR b.desemail = :search OR a.deslogin LIKE :search
        ORDER BY b.desperson
        LIMIT $start, $itemsPerPage;", [
            ':search'=> '%' . $search . '%'
        ]);

        //Contagem de elementos do nosso resultado
        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

        return array(
            'data'=>$results,
            'total'=>(int)$resultTotal[0]['nrtotal'],
            'pages'=>ceil($resultTotal[0]['nrtotal'] / $itemsPerPage)
        );

    }

}

?>