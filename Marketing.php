<?php

/****************************************************************************\
marketing.php - Promotional tools for marketing and sales online. MIT license.
Copyright (c) 2007-2022 DNP Online.
Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:
The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
DEALINGS IN THE SOFTWARE.
\****************************************************************************/


class Controller_Marketing extends Controller
{

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

    public function action_index() {

        $this->display('marketing/index.html');
    }

    public function action_generate() {

        $allowImg = array("jpg", "png", "jpeg", "gif");
        $allowVideo = array("mp4", 'webm', 'avi', 'flv');
        $allowTypes = array_merge($allowImg, $allowVideo);

        $uploadDir = 'upload/';
        $rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);

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
        $text_pos = _post('text_pos', 'center_bottom');
        $qr_effect = _post('qr_effect', '0');
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
        $new_file_path = $uploadDir . $new_file_name . "." . $file_extension;
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

        $d = urlencode($qr_url);
        $qr_request = Site::host() . "/barcode.php?s=qr&f=jpg&d=$d";
        $result = remote_request($qr_request);

        $qr_data = $result['response'];
        $qr_file_path = $uploadDir . $new_file_name . "_qr.jpg";
        $qr_realpath = $rootDir . "/" . $qr_file_path;
        file_put_contents($qr_file_path, $qr_data);
        $qr_link = Site::host() . "/" . $qr_file_path;
        $response['result_qr'] = $qr_link;

        if($file_type == 'image') {

            $result_image_path = $uploadDir . $new_file_name . "." . $file_extension;
            copy($new_file_path, $result_image_path);
            self::wotermak($result_image_path,$qr_file_path, $qr_pos);
            $image_link = Site::host() . "/" . $result_image_path;
            $result_image = "<a href='$image_link' target='_blank'>$image_link</a>";
            $response['result_image'] = $result_image;

            $video_realpath = $uploadDir . $new_file_name . ".mp4";
            exec("ffmpeg -loop 1 -i $result_image_path -c:v libx264 -t 15 -pix_fmt yuv420p -vf scale=1200:1200 -y $video_realpath");

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