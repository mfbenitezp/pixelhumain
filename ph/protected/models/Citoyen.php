<?php

class Citoyen
{
    const GAME_REZOTAGE    = 10;

    const NODE_ASSOCIATIONS     = 'associations';
    const NODE_APPLICATIONS     = 'applications';
    const NODE_EVENTS           = 'events';
    const NODE_POSITIONS        = 'positions';

    public static $types2Nodes = array(Group::TYPE_ASSOCIATION => self::NODE_ASSOCIATIONS,
                                        Group::TYPE_ENTREPRISE  => "employees",
                                        Group::TYPE_EVENT       => "participants",
                                        Group::TYPE_PROJECT     => "participants");

    public static function isCommunected(){
        $user = Yii::app()->mongodb->citoyens->findOne(array("_id"=>new MongoId(Yii::app()->session["userId"]))); 
        return (isset($user["cp"])) ? $user["cp"] : null;
    }
    public static function isAdminUser(){
        return ( isset(Yii::app()->session["userIsAdmin"]) && Yii::app()->session["userIsAdmin"] ) ;  
    }
    
    /**
     * Register or Login
     * on PH registration requires only an email 
     * test exists 
     * if exists > request pwd
     * otherwise add to DB 
     * send validation mail 
     * @param  [string] $email   email connected to the citizen account
     * @param  [string] $pwd   pwd connected to the citizen account
     * @return [type] [description]
     */
    public static function login($email,$pwd)
    {
        if(Yii::app()->request->isAjaxRequest && isset($email) && !empty($email))
        {
            Yii::app()->session["userId"] = null;
            Yii::app()->session["userEmail"] = null; 
            $account = Yii::app()->mongodb->citoyens->findOne(array("email"=>$email));
            if($account){
                if( empty( $account["pwd"] ) )
                {
                    if(empty($pwd)){
                        //send email to reset password
                        $pwd = uniqid('', true);
                        Yii::app()->mongodb->citoyens->update(array("email"=>$email), 
                                                              array('$set' => array("pwd"=>hash('sha256', $email.$pwd) )));
                        $message = new YiiMailMessage;
                        $message->view = 'validation';
                        $message->setSubject('Set your password - Pixel Humain');
                        $message->setBody(array("user"=>$account["_id"],
                                                "pwd"=>$pwd), 'text/html');
                        $message->addTo($email);
                        $message->from = Yii::app()->params['adminEmail'];
                        Yii::app()->mail->send($message);
                        
                        $res =  array("result"=>false, 
                                                "msg"=>"Vous n'aviez pas creer de mot de passe, un mot de passe temporaire vous a été envoyé par mail.") ;
                    } else {
                        //if a pwd was typed 
                        //it will be set as pwd and will login the person 
                        
                        Yii::app()->mongodb->citoyens->update(array("email"=>$email), 
                                                              array('$set' => array("pwd"=>hash('sha256', $email.$pwd) )));
                        
                        Yii::app()->session["userId"] = $account["_id"];
                        Yii::app()->session["userEmail"] = $account["email"]; 
                        
                        if( isset($account["isAdmin"]) && $account["isAdmin"] )
                            Yii::app()->session["userIsAdmin"] = $account["isAdmin"]; 
                            
                        Notification::add(array("type" => Notification::NOTIFICATION_LOGIN,
                                                "user" => $account["_id"]));
                        
                        $res = array("result"=>true,  "id"=>$account["_id"],"isCommunected"=>isset($account["cp"]));
                    }
                } 
                //if a pwd isn't set
                //but one is filled in the login field that will be the pwd
                elseif ( !empty($pwd) && $account["pwd"] == hash('sha256', $email.$pwd))
                {
                    Yii::app()->session["userId"] = $account["_id"];
                    Yii::app()->session["userEmail"] = $account["email"]; 
                    
                    if( isset($account["isAdmin"]) && $account["isAdmin"] )
                        Yii::app()->session["userIsAdmin"] = $account["isAdmin"]; 
                        
                    Notification::add(array("type" => Notification::NOTIFICATION_LOGIN,
                                            "user" => $account["_id"]));
                    
                    $res = array("result"=>true,  "id"=>$account["_id"],"isCommunected"=>isset($account["cp"]));
                } else 
                    $res = array("result"=>false, "msg"=>"Email ou Mot de Passe ne correspondent pas, rééssayez.");
                
               
            }
            else
                $res = array("result"=>false, "msg"=>"Vous devez remplir un email valide et un mot de passe .");
            
        } else
            $res = array("result"=>false, "msg"=>"Cette requete ne peut aboutir.");
        return $res;
    }

    /*
    a user registers with an email and a pwd minimum
    - check existance 
    - check email is valide
    - check pwd is filled 
    - insert to ciotyens collection
    - send validation Email 
    - add a system Notification for stats
     */
    public static function register( $email, $pwd )
    {
        if(Yii::app()->request->isAjaxRequest && isset($email) && !empty($email))
        {
            Yii::app()->session["userId"] = null;
            Yii::app()->session["userEmail"] = null; 
            $account = Yii::app()->mongodb->citoyens->findOne(array("email"=>$email));
            if(!$account)
            {
                //validate isEmail
                $name = "";
               if(preg_match('#^([\w.-])/<([\w.-]+@[\w.-]+\.[a-zA-Z]{2,6})/>$#',$email, $matches)) 
               {
                  $name = $matches[0];
                  $email = $matches[1];
               }
               
               if(!empty($pwd) && preg_match('#^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$#',$email)) 
               { 
                   
                   //new user is creating account 
                   $newAccount = array(
                                'email'=>$email,
                                'pwd' => hash('sha256', $email.$pwd),
                                'tobeactivated' => true,
                                'adminNotified' => false,
                                'created' => time()
                                );
                    if(!empty($name))
                        $newAccount["name"] = $name;
                    //add to DB
                    Yii::app()->mongodb->citoyens->insert($newAccount);
                   
                    //set session elements for global credentials
                    Yii::app()->session["userId"] = $newAccount["_id"]; 
                    Yii::app()->session["userEmail"] = $newAccount["email"];
                    
                    //send validation mail
                    //TODO : make emails as cron jobs
                    /*$message = new YiiMailMessage;
                    $message->view = 'validation';
                    $message->setSubject('Confirmer votre compte Pixel Humain');
                    $message->setBody(array("user"=>$newAccount["_id"]), 'text/html');
                    $message->addTo("oceatoon@gmail.com");//$email
                    $message->from = Yii::app()->params['adminEmail'];
                    Yii::app()->mail->send($message);*/
                    
                    //TODO : add an admin notification
                    Notification::add(array("type"=>Notification::NOTIFICATION_REGISTER,
                                            "user"=>$newAccount["_id"]));
                    
                    $res = array("result"=>true, "id"=>$newAccount);
               } else
                        $res = array("result"=>false, "msg"=>"Vous devez remplir un email valide et un mot de passe .");
            } else
                $res = array("result"=>true, "id"=>$account["_id"]);
        } else
            $res = array("result"=>false, "msg"=>"Cette requete ne peut aboutir.");

        return $res;
    }
/*
    a user communects with an email and a postal code
    - check existance 
    - check email is valide
    - check pwd is filled 
    - insert to ciotyens collection
    - send validation Email 
    - add a system Notification for stats
     */
    public static function communect( $email, $cp)
    {
        if(Yii::app()->request->isAjaxRequest && isset($email) && !empty($email))
        {
            $account = Yii::app()->mongodb->citoyens->findOne(array("email"=>$email));
            if(!$account)
            {
                //validate isEmail
                $name = "";
               if(preg_match('#^([\w.-])/<([\w.-]+@[\w.-]+\.[a-zA-Z]{2,6})/>$#',$email, $matches)) 
               {
                  $name = $matches[0];
                  $email = $matches[1];
               }
               
               if(preg_match('#^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$#',$email)) 
               { 
                   
                   //new user is creating account 
                   $newAccount = array(
                                'email'=>$email,
                                'tobeactivated' => true,
                                'adminNotified' => false,
                                'created' => time()
                                );
                    if(!empty($cp))
                        $newAccount["cp"] = $cp;
                    if(!empty($name))
                        $newAccount["name"] = $name;
                    //add to DB
                    Yii::app()->mongodb->citoyens->insert($newAccount);
                   
                    //set session elements for global credentials
                    Yii::app()->session["userId"] = $newAccount["_id"]; 
                    Yii::app()->session["userEmail"] = $newAccount["email"];
                    
                    //send validation mail
                    //TODO : make emails as cron jobs
                    /*$message = new YiiMailMessage;
                    $message->view = 'validation';
                    $message->setSubject('Confirmer votre compte Pixel Humain');
                    $message->setBody(array("user"=>$newAccount["_id"]), 'text/html');
                    $message->addTo("oceatoon@gmail.com");//$email
                    $message->from = Yii::app()->params['adminEmail'];
                    Yii::app()->mail->send($message);*/
                    
                    //TODO : add an admin notification
                    Notification::add(array("type"=>Notification::NOTIFICATION_COMMUNECTED,
                                            "user"=>$newAccount["_id"]));
                    
                    $res = array("result"=>true, "id"=>$newAccount);
               } else
                        $res = array("result"=>false, "msg"=>"Vous devez remplir un email valide.");
            } else
                $res = array("result"=>true, "id"=>$account["_id"]);
        } else
            $res = array("result"=>false, "msg"=>"Cette requete ne peut aboutir.");

        return $res;
    }

    //Registers a user into an application
    //by adding "applications":{"appKey":{appData}}
    //an application is defined in the application collection
    //if appkey is null no need to regiser any application
    public static function applicationRegistered($appKey, $email){
        $res = array();
        if( isset( Yii::app()->session["userId"]) && $appKey != null  ){
            //TODO : test application exists
            $application = Yii::app()->mongodb->applications->findOne( array( "key" => $appKey ) );  
            //check if application is registered on user account
            $account = Yii::app()->mongodb->citoyens->findOne( array( "email" => $email ) ); 
            //if not add it 
            if( isset( $application ) && !isset( $account["applications"][$appKey] ) )
            {
                $newInfos = array();
                if( !isset( $account[ 'applications' ] ))
                    $newInfos['applications'] = array( $appKey => array( "usertype" => $appKey)  );
                else {
                    $newInfos['applications'] = $account[ 'applications' ];
                    $newInfos['applications'] [$appKey] = array( "usertype" => $appKey)  ;
                }
                //if application requires rgistration confirmation or moderation
                if( isset( $application["registration"] ) && $application["registration"] == "mustBeConfirmed"  ){
                    $newInfos['applications'][$appKey]["registrationConfirmed"] = false ;
                    $res["addedRegistrationConfirmationRequest"] = true;
                }
                Yii::app()->mongodb->citoyens->update( array("email" => $email), 
                                                       array('$set' => $newInfos ) 
                                                      );
                $res["addedRegistration"] = $appKey;
            } else
                $res["isRegister".$appKey] = true;
        }
        return $res;
    }
   
}