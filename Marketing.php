<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);


class Controller_Marketing extends Controller
{
    protected $no_auth = true;
    protected $acl = true;

    protected function access_rules() {
        return array (
            User::ROLE_ADMIN => array('*'),
            User::ROLE_EDITOR => array('*'),
            User::ROLE_PREMIUM => array('index'),
            User::ROLE_USER => array('index'),
            User::ROLE_NOAUTH => array('index')

        );
    }

    public static function wotermak($img1,$img2,$pos){
        //типы файлов
        $type = pathinfo($img1);
        $type2 = pathinfo($img2);
        //создаем исходное изображение
        if($type['extension'] == 'jpg' or $type['extension'] == 'jpeg') {
            $img = imagecreatefromjpeg($img1);
        }
        if($type['extension'] == 'png') {
            $img = imagecreatefrompng($img1);
        }
        $arwater_img = getimagesize($img1); //узнаем размер переданного изображения, чтобы правильно рассчитать координаты наложения
        $water_width = $arwater_img[0]; //ширина исходного изображения
        $water_height = $arwater_img[1]; //высота исходного изображения
        $water_img_type = $arwater_img[2];
        $water_img_type = $arwater_img[$water_img_type-1];
        $water_img_size = $arwater_img[3];
        //создаем водный знак
        if($type2['extension'] == 'jpg' or $type['extension'] == 'jpeg') {
            $water_img = imagecreatefromjpeg($img2);
        }
        if($type2['extension'] == 'png') {
            $water_img = imagecreatefrompng($img2);
        }
        $water_size = getimagesize($img2); //узнаем размеры водного знака, чтобы правильно выполнить наложение
        $logo_h = $water_size[1]; //высота водного знака
        $logo_w = $water_size[0]; //ширинаа водного знака
        // левый верхний угол
        if($pos == 'left_top') {
            imagecopy ($img, $water_img, 0, 0, 0, 0, $logo_w, $logo_h);
        }
        // правый верхний угол
        if($pos == 'right_top') {
            imagecopy ($img, $water_img, $water_width-$logo_w, 0, 0, 0, $logo_w, $logo_h);
        }
        // правый нижний угол
        if($pos == 'right_bottom') {
            imagecopy ($img, $water_img, $water_width-$logo_w, $water_height - $logo_h, 0, 0, $logo_w, $logo_h);
        }
        //левый нижний угол
        if($pos == 'left_bottom') {
            imagecopy ($img, $water_img, 0, $water_height - $logo_h, 0, 0, $logo_w, $logo_h);
        }

        imagejpeg($img,$img1); //выводим, сохраняем изображение
        imagedestroy($img);
    }

    public static function text_on_image($img_path, $text, $pos) {
        $type = pathinfo($img_path);
        // (A) OPEN IMAGE
        if($type['extension'] == 'png') {
            $img = imagecreatefrompng($img_path);
        } else {
            $img = imagecreatefromjpeg($img_path);
        }

// (B) TEXT & FONT SETTINGS
        $fontFile = realpath("storage/fonts/OpenSans.ttf");
        $fontSize = 30;
        $fontColor = imagecolorallocate($img, 255, 255, 255);
        $angle = 0;

// (C) CALCULATE TEXT BOX POSITION
// (C1) GET IMAGE DIMENSIONS
        $img_W = imagesx($img);
        $img_H = imagesy($img);

// (C2) GET TEXT BOX DIMENSIONS
        $tSize = imagettfbbox($fontSize, $angle, $fontFile, $text);
        $text_W = max([$tSize[2], $tSize[4]]) - min([$tSize[0], $tSize[6]]);
        $text_H = max([$tSize[5], $tSize[7]]) - min([$tSize[1], $tSize[3]]);

// (C3) POSITION OF THE TEXT BLOCK
        if($pos == 'top') {
            $X = 10;
            $Y = 10;
        }
        if($pos == 'center') {
            $X = CEIL(($img_W - $text_W) / 2);
            $X = $X<0 ? 0 : $X;
            $Y = CEIL(($img_H - $text_H) / 2);
            $Y = $Y<0 ? 0 : $Y;
        }
        if($pos == 'bottom') {
            $X = 10;
            $Y = $img_H - 10;
        }

// (D) DRAW TEXT ON IMAGE
        imagettftext($img, $fontSize, $angle, $X, $Y, $fontColor, $fontFile, $text);

// (E) SAVE IMAGE
        if($type['extension'] == 'png') {
            imagepng($img,$img_path);
        } else {
            imagejpeg($img,$img_path);
        }
        imagedestroy($img);
    }

    public function action_index() {

        $this->display('marketing/index.html');
    }

    public function action_generate() {

        $allowImg = array("jpg", "jpeg");
        $allowVideo = array("mp4", 'webm', 'avi', 'flv');
        //$allowTypes = array_merge($allowImg, $allowVideo);
        $allowTypes = $allowImg;

        $uploadDir = 'upload/';
        $rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);
        $font_path = realpath("storage/fonts/OpenSans.ttf");

        $response = array(
            'status' => '',
            'message' => 'Upload failed, please try again.',
            'result_qr' => "",
            'result_image' => "",
            'result_video' => ""
        );

        $url = _post("url", "");
        $qr_url = _post("qr_url", "");
        $text = _post("text", "");
        $qr_size = _post('qr_size', 'qr');
        $qr_pos = _post('qr_pos', 'right_bottom');
        $text_pos = _post('text_pos', 'center');
        $video_effect = _post('video_effect', '0');
        $file = $_FILES["file"]["name"];

        $file_type = "";
        $file_name = "";
        $file_extension = "";
        if(!empty($file)){
            $file_name = basename($_FILES["file"]["name"]);
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        } else if(!empty($url)) {
            $url_array = explode("/", $url);
            $file_name = end($url_array);
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        } else {
            $response['status'] = "Error";
            $response['message'] = "No source file found";
            $this->json($response);
        }

        if(!in_array($file_extension, $allowTypes)){
            $response['status'] = "Error";
            $response['message'] = "Not allowed source file extension";
            $this->json($response);
        }

        if(in_array($file_extension, $allowImg)){
            $file_type = "image";
        } else {
            $file_type = "video";
        }

        $new_file_name = rand();
        $new_file_path = $uploadDir . $new_file_name . "_orig." . $file_extension;
        $new_file_realpath = $rootDir . "/" . $new_file_path;
        if(!empty($file)){
            if(move_uploaded_file($_FILES["file"]["tmp_name"], $new_file_path)){

            } else {
                $response['status'] = "Error";
                $response['message'] = "Sorry, there was an error uploading your file.";
                $this->json($response);
            }

        } else {
            $file_data = file_get_contents($url);
            if(file_put_contents($new_file_path, $file_data)) {

            } else {
                $response['status'] = "Error";
                $response['message'] = "Sorry, there was an error uploading your file.";
                $this->json($response);
            }
        }

        $t = imagecreatefromjpeg($new_file_path);
        $video_X = imagesx($t);
        $video_X = ($video_X % 2) ? $video_X+1 : $video_X;
        $video_Y = imagesy($t);
        $video_Y = ($video_Y % 2) ? $video_Y+1 : $video_Y;


        $video_scale = $video_X."x".$video_Y;
        $min_s = min($video_X, $video_Y);

        if($qr_url != "") {

            $d = urlencode($qr_url);
            $qr_request = Site::host() . "/barcode.php?s=qr&f=jpg&d=$d";
            $result = remote_request($qr_request);

            $qr_data = $result['response'];
            $qr_file_path = $uploadDir . $new_file_name . "_qr.jpg";
            $qr_realpath = $rootDir . "/" . $qr_file_path;
            file_put_contents($qr_file_path, $qr_data);

            $t = imagecreatefromjpeg($qr_file_path);
            $x = imagesx($t);
            $y = imagesy($t);
            $new_width = intval($min_s * 0.15);
            $new_height = intval($min_s * 0.15);
            $s = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($s, $t, 0, 0, 0, 0, $new_width, $new_height, $x, $y);
            imagejpeg($s, $qr_file_path);

            $qr_link = Site::host() . "/" . $qr_file_path;
            $response['result_qr'] = $qr_link;
        }

        if($file_type == 'image') {

            $result_image_path = $uploadDir . $new_file_name . "." . $file_extension;
            copy($new_file_path, $result_image_path);

            if($response['result_qr'] != "") {
                self::wotermak($result_image_path, $qr_file_path, $qr_pos);
            }

            if($text != "") {
                self::text_on_image($result_image_path, $text, $text_pos);
            }

            $image_link = Site::host() . "/" . $result_image_path;
            $result_image = "<a href='$image_link' target='_blank'>$image_link</a>";
            $response['result_image'] = $result_image;

            $video_realpath = $uploadDir . $new_file_name . ".mp4";

            if($video_effect == "0") {
                exec("ffmpeg -loop 1 -i $result_image_path -c:v libx264 -t 15 -pix_fmt yuv420p -vf scale=$video_scale -y $video_realpath");
            } else if($video_effect == "1") {
                $video_dest = $uploadDir . $new_file_name . "_ef.mp4";
                exec("ffmpeg -loop 1 -i $new_file_path -filter_complex \"[0:v]zoompan=z='min(max(zoom,pzoom)+0.015,2)':d=1.5:x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':s=$video_scale,trim=duration=15[v]\" -map \"[v]\" -y $video_dest");

                if($response['result_qr'] != "") {

                    if($qr_pos == 'left_top') {
                        $X = '10';
                        $Y = '10';
                    }
                    // правый верхний угол
                    if($qr_pos == 'right_top') {
                        $X = '(W-w-10)';
                        $Y = '10';

                    }
                    // правый нижний угол
                    if($qr_pos == 'right_bottom') {
                        $X = '(W-w-10)';
                        $Y = '(H-h-10)';
                    }
                    //левый нижний угол
                    if($qr_pos == 'left_bottom') {
                        $X = '10';
                        $Y = '(H-h-10)';
                    }
                    $video_src = $video_dest;
                    $video_dest = $uploadDir . $new_file_name . "_ef_qr.mp4";
                    exec("ffmpeg -i $video_src -i $qr_realpath -filter_complex \"[0:v][1:v] overlay=$X:$Y:enable='between(t,0,15)'\" -pix_fmt yuv420p -c:a copy -y $video_dest");

                }

                if($text != "") {
                    if($text_pos == 'top') {
                        $X = '10';
                        $Y = '10';
                    }
                    if($text_pos == 'center') {
                        $X = '(w/2-text_w/2)';
                        $Y = '(h/2-text_h/2)';
                    }
                    if($text_pos == 'bottom') {
                        $X = '10';
                        $Y = '(h-text_h-10)';
                    }
                    $video_src = $video_dest;
                    $video_dest = $uploadDir . $new_file_name . "_ef_txt.mp4";
                    exec("ffmpeg -i $video_src -vf \"[in]drawtext=fontsize=48:fontcolor=White:fontfile='$font_path':text='$text':x=$X:y=$Y\" -codec:a copy -y $video_dest");
                }

                copy($video_dest, $video_realpath);

            }

            $video_link = Site::host() . "/" . $video_realpath;
            $result_video = "<a href='$video_link' target='_blank'>$video_link</a>";
            $response['result_video'] = $result_video;

        } else {

        }

        $response['status'] = "OK";
        $response['message'] = "File uploaded";
        $this->json($response);

    }
}