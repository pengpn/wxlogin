<?php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error', 'get-code','wxlogin','test'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionGetCode()
    {
        if($_GET['state']=='sip_Wxlogin'){
            $control = 'r=site/wxlogin&code=';
            if(isset($_GET['code'])){
                // 跳转到上一个页面将最后一个符号/开始的字符换成$control 如：// www.123.com/login   回调网页再跳转到：// www.123.com/wxlogin?code='.$_GET['code'];
                header("Location: http://wxlogin.test?".$control.$_GET['code']);
                die;
            }
        }
    }

    public function actionTest()
    {
        return $this->render('index');
        var_dump(123);
        var_dump(Yii::$app->request->get());exit;
    }

    public function actionWxlogin()
    {

        $code = Yii::$app->request->get('code');
        $corpid = 'ww484cd3aedaec5475';  //  这里   <------
        $secret = 'MF6FmNsbV9ewSYYfyEa_UpmJ0N8xqeNQC4JpdaR6LPI'; //  这里   <------
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid='.$corpid.'&corpsecret='.$secret;
        $token = $this->https_request($url); //以appid和secret获取token   <-----
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token='.$token['access_token'].'&code='.$code.'&state=sip_Wxlogin';
        $usersinfo = $this->https_request($url); //以token和code获取企业微信用户userid

        if(isset($usersinfo['UserId'])) {
            $url = 'https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token=' . $token['access_token'] . '&userid=' . $usersinfo['UserId'];
            $userinfo = $this->https_request($url); //以token和企业微信用户userid获取该user基本信息
            var_dump($userinfo);exit;
            if ($userinfo['errcode'] == 0) {
//                $this->db = new Sysdb;
//                $res = $this->db->query('select * from users where openid="' . $userinfo['userid'] . '"');
                if ($res) {   //如果该用户存在本地用户表
                    //直接登录
                } else {  //如果该用户不存在本地用户表
                    //跳转到注册页面
                }
            }
        }
    }


    private function https_request($url){
        $curl=curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data=json_decode(curl_exec($curl), true);
        // $data=curl_exec($curl);
        curl_close($curl);
        return $data;
    }
}
