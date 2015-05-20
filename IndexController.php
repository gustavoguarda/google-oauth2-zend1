<?php

class Site_IdentificacaoController extends App_Controller_Site
{
    public function loginGoogleAction()
    {
        try {
            $session = new Zend_Session_Namespace('access_token');

            if (isset($session->access_token) && $session->access_token) {
                return $this->forward('retorno-google-o-auth2', 'identificacao', 'site');
            } else {
                $googleOAuth2 = APPLICATION_PATH . '/../application/configs/google-o-auth2.json';

                $client = new Google_Client();
                $client->setAuthConfigFile($googleOAuth2);
                $client->setAccessType("online");
                $client->setApplicationName("Gran Cursos Online");
                $client->addScope("https://www.googleapis.com/auth/userinfo.email");

                $session->access_token = $client->getAccessToken();
                if (isset($session->access_token) && $session->access_token) {
                    $client->setAccessToken($session->access_token);
                } else {
                    $authUrl = $client->createAuthUrl();
                    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
                }
            }
        } catch (App_Exception $e) {
            $this->setErrorMessage("Erro ao autenticar com o Google", array($e->getErrorMessage()));
            $this->redirect('identificacao', 'index', 'site');
        }
        exit();
    }

    public function retornoGoogleOAuth2Action()
    {
        try {
            $session = new Zend_Session_Namespace('access_token');

            $googleOAuth2 = APPLICATION_PATH . '/../application/configs/google-o-auth2.json';
            $client = new Google_Client();
            $client->setAuthConfigFile($googleOAuth2);
            $client->setAccessType("online");
            $client->setApplicationName("Gran Cursos Online");
            $client->addScope("https://www.googleapis.com/auth/userinfo.email");

            if (isset($_GET['code'])) {
                $client->authenticate($_GET['code']);
                $session->access_token = $client->getAccessToken();
            }

            if (isset($session->access_token) && $session->access_token) {
                $client->setAccessToken($session->access_token);
            }

            //Send Client Request
            $objOAuthService = new Google_Service_Oauth2($client);

            if ($client->getAccessToken()) {
                $oUserInfo = $objOAuthService->userinfo->get();
                if (!empty($oUserInfo) && $oUserInfo->verifiedEmail) {
                    // Tratamos o nome e sobrenome para o nosso padrão
                    $aNome     = explode(" ", $oUserInfo->givenName);
                    $nome      = $aNome[0];
                    $sobrenome = $aNome[1] . ' ' . $oUserInfo->familyName;

                    $mAluno = new Model_DbTable_Sigue_Aluno();
                    $rAluno = $mAluno->fetchRowByEmail($oUserInfo->email);

                    if (is_null($rAluno)) {
                        $idAluno = $mAluno->salvar(array(
                            'Nome'      => $nome,
                            'Sobrenome' => $sobrenome,
                            'Email'     => $oUserInfo->email,
                            'Sexo'      => trim(ucfirst(substr($oUserInfo->gender, 0, 1))),
                            'senhaMd5'  => md5(App_Function::getRandomString())
                        ));
                    }

                    $user = $mAluno->autenticar(
                        $rAluno->Email, $rAluno->senhaMd5, false
                    );

                    $url = 'aluno/espaco';

                    $request = Zend_Controller_Front::getInstance()->getRequest();
                    $baseUrl = $request->getScheme() . '://' . $request->getHttpHost() . rtrim($request->getBaseUrl(), '/');
                    header('Location: ' . filter_var($baseUrl . DIRECTORY_SEPARATOR . $url, FILTER_SANITIZE_URL));
                }
                $session->access_token = $client->getAccessToken();
            } else {
                $authUrl = $client->createAuthUrl();
                header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
            }
        } catch (App_Exception $e) {
            $this->setErrorMessage("Erro ao autenticar com o Google", array($e->getErrorMessage()));
            $this->redirect('identificacao', 'index', 'site');
        }
        exit;
    }
}
