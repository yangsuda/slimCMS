<?php
/**
 * 后台登陆控制类
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Control\admincp;

use App\Core\Forms;
use App\Model\admincp\LoginModel;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Helper\Crypt;

class LoginControl extends ControlAbstract
{
    public function login()
    {
        $formhash = self::input('formhash');
        if ($formhash) {
            $ccode = (string)self::input('ccode');
            $img = new \Securimage();
            if (!$img->check($ccode)) {
                $output = self::$output->withCode(24023);
                return $this->response($output);
            }
            $res = Forms::submitCheck($formhash);
            if ($res->getCode() != 200) {
                return $this->response($res);
            }
            $userid = self::input('userid');
            $pwd = self::input('pwd');
            $referer = self::input('referer', 'url');
            $res = LoginModel::loginCheck($userid, $pwd, $referer);
            if ($res->getCode() == 200) {
                isset($_SESSION) ? '' : session_start();
                $_SESSION['adminAuth'] = Crypt::encrypt($res->getData()['id']);
            }
            return $this->response($res);
        }

        isset($_SESSION) ? '' : session_start();
        $adminAuth = (string)aval($_SESSION, 'adminAuth');
        $auth = Crypt::decrypt($adminAuth);
        if (is_numeric($auth)) {
            self::directTo(self::$output->withReferer(self::url('?p=main/index')));
        }
        return $this->view();
    }

    /**
     * 退出
     * @return array
     */
    public function logout()
    {
        isset($_SESSION) ? '' : session_start();
        unset($_SESSION['adminAuth']);
        $referer = self::url('?p=login&referer=' . urlencode(self::$config['referer']));
        $output = self::$output->withCode(200, 21047)->withReferer($referer);
        return self::directTo($output);
    }

    /**
     * 验证码生成
     */
    public function captcha()
    {
        $img = new \Securimage();
        $img->code_length = 4;
        $img->image_width = 80;
        $img->image_height = 40;
        $img->ttf_file = CSDATA . 'fonts/INDUBITA.TTF';
        $img->text_color = new \Securimage_Color('#009D41');
        $img->charset = '0123456789';
        $img->num_lines = 0;
        $img->noise_level = 1;
        return $img->show();
    }

}