<?php

class IndexController extends Zend_Controller_Action
{

    public function indexAction()
    {
    }

    public function loginGoogleAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        try {
            $googleOAuth2 = APPLICATION_PATH . '/../application/configs/google-o-auth2.json';

            $session = new Zend_Session_Namespace('access_token');

            $client = new Google_Client();
            $client->setAuthConfigFile($googleOAuth2);
            $client->setAccessType("online");
            $client->setApplicationName("");
            $client->addScope("https://www.googleapis.com/auth/userinfo.email");
            $client->addScope("https://www.googleapis.com/auth/userinfo.profile");

            $session->access_token = $client->getAccessToken();
            if (!empty($session->access_token)) {
                $client->setAccessToken($session->access_token);
            } else {
                $authUrl = $client->createAuthUrl();
                header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
            }
        } catch (Zend_Exception $e) {
            $this->_helper->flashMessenger->addMessage("Erro ao autenticar com o Google " . array($e->getErrorMessage()));
            $this->redirect('identificacao/index/');
        }
    }

    public function retornoGoogleOAuth2Action()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        try {
            $session = new Zend_Session_Namespace('access_token');

            $googleOAuth2 = APPLICATION_PATH . '/../application/configs/google-o-auth2.json';
            $client = new Google_Client();
            $client->setAuthConfigFile($googleOAuth2);
            $client->setAccessType("online");
            $client->setApplicationName("");

            if (isset($_GET['code'])) {
                $client->authenticate($_GET['code']);
                $session->access_token = $client->getAccessToken();
            }

            if (!empty($session->access_token)) {
                $client->setAccessToken($session->access_token);
            }

            //Send Client Request
            $objOAuthService = new Google_Service_Oauth2($client);

            if ($client->getAccessToken()) {
                $oUserInfo = $objOAuthService->userinfo->get();
                if (!empty($oUserInfo) && $oUserInfo->verifiedEmail) {
                    $url = 'identificacao/espaco';

                    $request = Zend_Controller_Front::getInstance()->getRequest();
                    $baseUrl = $request->getScheme() . '://' . $request->getHttpHost() . rtrim($request->getBaseUrl(), '/');
                    header('Location: ' . filter_var($baseUrl . DIRECTORY_SEPARATOR . $url, FILTER_SANITIZE_URL));
                }
                $session->access_token = $client->getAccessToken();
            } else {
                $authUrl = $client->createAuthUrl();
                header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
            }
        } catch (Zend_Exception $e) {
            $this->_helper->flashMessenger->addMessage("Erro ao autenticar com o Google " . array($e->getErrorMessage()));
            $this->redirect('identificacao/index/');
        }
    }
}

