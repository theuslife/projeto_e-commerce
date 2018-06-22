<?php

namespace Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model
{

    const SESSION = "User";
    const SECRET = "HcodePhp7_Secret";

    //Login
    public static function login($login, $password)
    {

        $sql = new Sql();
        
        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGINN", array(
            ":LOGINN"=>$login
        ));
        
        if(count($results) === 0 )
        {
            throw new \Exception("Usuário inexistente ou senha inválida", 1);
        }
        
        $data = $results[0];
        

         //Configurando o Login do usuário
        if($data["despassword"] == $password)
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

           
        /*  Login verificando a senha criptografada
            if(password_verify($password, $data["despassword"]) === true)
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
        */
    }

    //Checks user login
    public static function verifyLogin($inadmin = true)
    {
        if(!isset($_SESSION[user::SESSION])  || !$_SESSION[User::SESSION] || !(int)$_SESSION[User::SESSION]["iduser"] > 0 || (bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin)
        {
            header("Location: /admin/login");
            exit;
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
            ":despassword"=>$this->getdespassword(),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));
        
        $this->setData($results[0]);

    }

    //Function ''get'' id from user
    public function get($iduser)
    {
        
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
            ":iduser"=>$iduser
        ));

        $this->setData($results[0]);

    }

    //User update
    public function update()
    {
        
        $sql = new Sql();

        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":iduser"=>$this->getiduser(),
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
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
    public static function getForgot($email)
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

                $link = "http://www.e-commerce.com.br/admin/forgot/reset?code=$code";

                $mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir Senha da Hcode Store", "forgot", array(
                    "name"=>$data["desperson"],
                    "link"=>$link
                ));

                $mailer->send();
                return $data;

            }

        }

    }

    public static function validForgotDecrypt($code)
    {
        
        $code = base64_decode($code);
        
        //Encrypt and Decrypt
        $code = mb_substr($code, openssl_cipher_iv_length('aes-256-cbc'), null, '8bit');
        
        $iv = mb_substr($code, 0, openssl_cipher_iv_length('aes-256-cbc'), '8bit');

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

}

?>