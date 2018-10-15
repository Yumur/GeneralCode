<?php

/*
  This is an example class script proceeding secured API
  To use this class you should keep same as query string and function name
  Ex: If the query string value rquest=delete_user Access modifiers doesn't matter but function should be
  function delete_user(){
  You code goes here
  }
  Class will execute the function dynamically;

  usage :
 * 
 * 
 * 
 * 
 * 

  $object->response(output_data, status_code);
  $object->_request	- to get santinized input

  output_data : JSON (I am using)
  status_code : Send status message for headers

  Add This extension for localhost checking :
  Chrome Extension : Advanced REST client Application
  URL : https://chrome.google.com/webstore/detail/hgmloofddffdnphfgcellkdfbfbjeloo

  I used the below table for demo purpose.

  CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_fullname` varchar(25) NOT NULL,
  `user_email` varchar(50) NOT NULL,
  `user_password` varchar(50) NOT NULL,
  `user_status` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
 */

require_once("Rest.inc.php");
require_once '../frontcommon/emailer.php';

class API extends REST {

    public $data = "";

    const DB_SERVER = "test";
    const DB_USER = "test";
    const DB_PASSWORD = "*test";
    const DB = "test";

    private $db = NULL;
    private $mysqli = NULL;
    private $objEmailer;

    public function __construct() {
        parent::__construct();    // Init parent contructor
        $this->dbConnect();     // Initiate Database connection

        $this->objEmailer = new emailer();
    }

    /*
     *  Database connection 
     */

    private function dbConnect() {
        $this->mysqli = new mysqli(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD, self::DB);
    }

    /*
     * Public method for access api.
     * This method dynmically call the method based on the query string
     *
     */

    public function processApi() {
        $func = strtolower(trim(str_replace("/", "", $_REQUEST['rquest'])));
        if ((int) method_exists($this, $func) > 0)
            $this->$func();
        else
            $this->response('', 404);    // If the method not exist with in this class, response would be "Page not found".
    }

    /*
     * 	Simple login API
     *  Login must be POST method
     *  email : <USER EMAIL>
     *  pwd : <USER PASSWORD>
     */

    private function parentcategory() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
         $query = "select pct.ParentCategoryId,pct.ParentCategoryName from parentcategory pct where pct.IsActive=1";
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }
    
    private function navcategory() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $parentcatid = $this->_request['parentcatid'];
        $query = "select ct.CategoryId,ct.CategoryName from category ct where ct.IsActive=1 and ct.ParentCategoryId=".$parentcatid;
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }
    private function category() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }

        $parentcatid = $this->_request['parentcatid'];
        $offsetcount = $this->_request['offset'];

        if ($parentcatid != NULL) {
            $parentcat = " AND ct.ParentCategoryId= " . $parentcatid;
        } else {
            $parentcat = " AND 1=1 ";
        }

        if ($offsetcount != NULL) {
            $offset = $offsetcount . ",";
        } else {
            $offset = " 0,";
        }


        $sqlindexcategory = "select ozmaincat.CategoryId from ozin_manageindexcategory ozmaincat";
        $indexcat = $this->mysqli->query($sqlindexcategory);
        $resultindexcat = array();
        while ($rowindexcat = $indexcat->fetch_assoc()) {
            $resultindexcat[] = $rowindexcat;
        }

        foreach ($resultindexcat as $resultindex) {
            $resultindexcategory[] = $resultindex['CategoryId'];
        }

        $indexcategory = implode(',', $resultindexcategory);
        $query = "  select SQL_CALC_FOUND_ROWS ct.CategoryId,ct.ParentCategoryId,ct.CategoryName, ozvideo.Videotitle,ozvideo.VideoDuration from category ct 
                    INNER JOIN subcategory st ON ct.CategoryId=st.CategoryId
                    inner join ozin_video ozvideo on ct.CategoryId=ozvideo.CategoryId
                    where ct.IsActive=1 AND st.IsActive=1 " . $parentcat . " group by ct.CategoryId ORDER BY FIELD(ct.CategoryId," . $indexcategory . ")  limit $offset 4";

        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    private function categoryvideo() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $categoryid = $this->_request['category'];
        if ($categoryid != NULL) {
            $category = " and ovideo.CategoryId=" . $categoryid . " ";
        } else {
            $category = ' and 1=1';
        }

        $query = "SELECT ovideo.VideoId,ovideo.TagId,ovideo.Videotitle,ovideo.ImageName,ovideo.CategoryId, ovideo.ImagePathThumb,ovideo.ModifiedDate,DATEDIFF(CURDATE(), ovideo.ModifiedDate) as uploadday,ozvidviw.ReView,ozvidviw.EdView,GREATEST(ozvidviw.ReView, ozvidviw.EdView) as actualview,ovideo.VideoDuration
                  FROM ozin_video ovideo
                  LEFT JOIN ozin_videoviews ozvidviw ON ovideo.VideoId=ozvidviw.VideoId
                  WHERE ovideo.IsActive=1 AND ovideo.IsDelete=0 " . $category;
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    private function subcategory() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }

        $categoryid = $this->_request['categoryid'];
        $offsetcount = $this->_request['offset'];

        if ($categoryid != NULL) {
            $category = " AND ct.CategoryId= " . $categoryid;
        } else {
            $category = " AND 1=1 ";
        }


        if ($offsetcount != NULL) {
            $offset = $offsetcount . ",";
        } else {
            $offset = " 0,";
        }
        $sqlindexcategory = "select ozmaincat.CategoryId from ozin_manageindexcategory ozmaincat";
        $indexcat = $this->mysqli->query($sqlindexcategory);
        $resultindexcat = array();
        while ($rowindexcat = $indexcat->fetch_assoc()) {
            $resultindexcat[] = $rowindexcat;
        }

        foreach ($resultindexcat as $resultindex) {
            $resultindexcategory[] = $resultindex['CategoryId'];
        }


        $indexcategory = implode(',', $resultindexcategory);
          $query = "select ct.CategoryId,ct.ParentCategoryId,ct.CategoryName, ozvideo.Videotitle ,st.SubCategoryName,st.SubCategoryId from category ct 
                    INNER JOIN subcategory st ON ct.CategoryId=st.CategoryId
                    inner join ozin_video ozvideo on st.SubCategoryId=ozvideo.SubCategoryId
                    where ct.IsActive=1 AND st.IsActive=1 AND ozvideo.IsActive=1 " . $category . " group by st.SubCategoryId   limit $offset 4";

        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    private function subcategoryvideo() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $subcategoryid = $this->_request['subcategory'];
        if ($subcategoryid != NULL) {
            $subcategory = " and ovideo.SubCategoryId=" . $subcategoryid . " ";
        } else {
            $subcategory = ' and 1=1';
        }
        $offsetcount = $this->_request['offset'];
        if ($offsetcount != NULL) {
            $offset = "limit " . $offsetcount . ",6";
        } else {
            $offset = " ";
        }
        $query = "SELECT ovideo.VideoId,ovideo.TagId,ovideo.Videotitle,ovideo.ImageName,ovideo.CategoryId, ovideo.ImagePathThumb,ovideo.SubCategoryId,ovideo.ModifiedDate,DATEDIFF(CURDATE(), ovideo.ModifiedDate) as uploadday,ozvidviw.ReView,ozvidviw.EdView,GREATEST(ozvidviw.ReView, ozvidviw.EdView) as actualview,ovideo.VideoDuration
                  FROM ozin_video ovideo 
                  LEFT JOIN ozin_videoviews ozvidviw ON ovideo.VideoId=ozvidviw.VideoId
                  WHERE ovideo.IsActive=1 AND ovideo.IsDelete=0 " . $subcategory . " " . $offset . " ";
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    private function viewallsubcategory() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }

        $subcategoryid = $this->_request['subcategoryid'];
        $offsetcount = $this->_request['offset'];

        if ($subcategoryid != NULL) {
            $subcategory = " AND ozvideo.SubCategoryId= " . $subcategoryid;
        } else {
            $subcategory = " AND 1=1 ";
        }
        if ($offsetcount != NULL) {
            $offset = $offsetcount . ",";
        } else {
            $offset = " 0,";
        }
        $sqlindexcategory = "select ozmaincat.CategoryId from ozin_manageindexcategory ozmaincat";
        $indexcat = $this->mysqli->query($sqlindexcategory);
        $resultindexcat = array();
        while ($rowindexcat = $indexcat->fetch_assoc()) {
            $resultindexcat[] = $rowindexcat;
        }

        foreach ($resultindexcat as $resultindex) {
            $resultindexcategory[] = $resultindex['CategoryId'];
        }


        $indexcategory = implode(',', $resultindexcategory);
        $query = "select ct.CategoryId,ct.ParentCategoryId,ct.CategoryName, ozvideo.Videotitle ,st.SubCategoryName,st.SubCategoryId from category ct 
                    INNER JOIN subcategory st ON ct.CategoryId=st.CategoryId
                    inner join ozin_video ozvideo on st.SubCategoryId=ozvideo.SubCategoryId
                    where ct.IsActive=1 AND st.IsActive=1 " . $subcategory . " limit $offset 24";

        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    private function banner() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }

        $query = "select ozbann.BannerId,ozbann.BannerName,ozbann.BannerSlogan,ozbann.ImageName,ozbann.ImagePath,ozbann.VideoId,ozbann.CategoryId from ozin_banner ozbann where ozbann.IsActive=1 and ozbann.IsDelete=0";
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    private function video() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $videoid = $this->_request['videoid'];

        if ($videoid != NULL) {
            $video = " AND ovideo.VideoId=" . $videoid . " ";
        } else {
            $video = " And 1=1";
        }

        $limit = $this->_request['limit'];
        if ($limit != NULL) {
            $limitset = " limit " . $limit . " ";
        } else {
            $limitset = "";
        }
        $query = " SELECT ovideo.VideoId,ovideo.Videotitle,ovideo.CategoryId,ovideo.SubCategoryId,ovideo.TagId,ovideo.StartAddId,
                  ovideo.EndAddId,ovideo.MidAddId,ovideo.MidAddTime,ovideo.VideoName,ovideo.VideoPath,ovideo.ImageName,ovideo.ImagePathThumb,ovideo.ImagePathMain,ovideo.Description,ovideo.SubtitleText,
                  ozvidviw.VideoId as viewvideoid,ozvidviw.ReView,ozvidviw.EdView, GREATEST(ozvidviw.ReView, ozvidviw.EdView) AS VideoViews, ovideo.TagId
                  FROM ozin_video ovideo LEFT JOIN ozin_videoviews ozvidviw ON ovideo.VideoId=ozvidviw.VideoId
                  LEFT JOIN tags tg on ovideo.TagId=tg.TagId WHERE ovideo.IsActive=1 AND ovideo.IsDelete=0 " . $video . " " . $limitset;
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    private function tag() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $tagid = $this->_request['tagid'];
        if ($tagid != NULL) {
            $tag = " AND tg.TagId in (" . $tagid . " )";
        } else {
            $tag = " And 1=1";
        }
        $query = "select tg.TagId,tg.TagName from tags tg where  tg.IsActive=1 " . $tag;
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function registration() {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        $code = $this->_request['code'];
        $name = $this->_request['username'];
        $email = $this->_request['useremail'];
        $password = $this->_request['password'];
        $confirmpassword = $this->_request['confirmpassword'];
        $phoneno = $this->_request['phoneno'];
        $address = $this->_request['address'];
        $gender = $this->_request['gender'];
        $dob = $this->_request['dob'];
        $dob = date('Y-m-d ', strtotime($dob));
        $confirmcode = md5(uniqid(rand()));

        $query = "select * from user u where u.UserEmail =  '$email'  ";
        $chkemail = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
        if ($chkemail->num_rows == 0) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $queryinsertuser = "INSERT INTO ozin_tmpuser ( UserFirstName, UserEmail,Country_code, UserPhone,UserPassword , address, Gender, UserDOB,ConfirmCode) "
                        . "VALUES ('$name', '$email','$code', '$phoneno','" . md5($password) . "',' $address', '$gender',  '$dob','$confirmcode')";
                $insertuser = $this->mysqli->query($queryinsertuser) or die($this->mysqli->error . __LINE__);
                if ($insertuser == 1) {
                    $userarr = array(
                        'username' => $name,
                        'useremail' => $email,
                        'confirmcode' => $confirmcode
                    );
                    $userbody = $this->objEmailer->userregister($userarr);
                    $userto = $email;
                    $userfrom = 'Ozintv.com';
                    $useremailsubject = 'Activataion Link';

                    $headers = "From:" . $userfrom . "\r\n";
                    $headers .= "To :" . $userto . "\r\n";
                    $headers .= "MIME-Version: 1.0 " . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";


//                    $sendmail = mail($userto, $useremailsubject, $userbody, $headers);

                    $sendmail = $this->objEmailer->sendgridEmail($userto, $userfrom, $useremailsubject, $userbody);
                    if ($sendmail == '1') {
                        $error = array('status' => "1", "msg" => "Registration successfully");
                        $this->response($this->json($error), 200);
                    } else {
                        $this->response('Something Went Wrong', 204); // If no records "No Conte
                    }
                }
            }
        } else {
            $error = array('status' => "2", "msg" => "Email Already Exist");
            $this->response($this->json($error), 200);
        }
    }

    public function login() {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $loginemail = $this->_request['loginemail'];
        $loginpassword = $this->_request['loginpwd'];

        $querylogin = "select * from user u where u.UserEmail = '$loginemail' and  u.UserPassword='" . md5($loginpassword) . "'";
        $loginuser = $this->mysqli->query($querylogin) or die($this->mysqli->error . __LINE__);
        if ($loginuser->num_rows > 0) {
            $result = array();
            while ($row = $loginuser->fetch_assoc()) {
                $result[] = $row;
                session_start();
                $_SESSION['userid'] = $row['UserId'];
                $_SESSION['username'] = $row['UserFirstName'];
                $_SESSION['useremail'] = $row['UserEmail'];
                $_SESSION['logintype'] = 'userlogin';
            }
            $lastlogin = " UPDATE `user` SET `ModifiedDate`=now(),`LastLogin`=now() WHERE `UserId` = " . $_SESSION['userid'];
            $loginuser = $this->mysqli->query($lastlogin) or die($this->mysqli->error . __LINE__);

            $error = array('status' => "1", "msg" => "login successfully");
            $this->response($this->json($error), 200);
//            echo json_encode('1');
        } else {
            $error = array('status' => "2", "msg" => "Invalid Email address or Password");
            $this->response($this->json($error), 200);
        }
    }

    public function addtofavorites() {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $userid = $this->_request['userid'];
        $videoid = $this->_request['videoid'];

        $querychkfavo = "select * from ozin_favorites ofav where ofav.Fav_videoid= '" . $videoid . "' and ofav.Fav_userid='" . $userid . "'";
        $loginchkfav = $this->mysqli->query($querychkfavo) or die($this->mysqli->error . __LINE__);
//        echo$loginchkfav->num_rows;
        if ($loginchkfav->num_rows == 0) {

            $queryinsertfavorites = "INSERT INTO ozin_favorites ( Fav_videoid, Fav_userid, IsActive,IsDelete , CreatedDate) "
                    . "VALUES ('$videoid', '$userid', '1','0',now())";
            $insertfavo = $this->mysqli->query($queryinsertfavorites) or die($this->mysqli->error . __LINE__);

            if ($insertfavo = true) {
                $error = array('status' => 'Inserted', "msg" => "Video Added to Favorites Successfully !");
                $this->response($this->json($error), 200);
            } else {
                $error = array('status' => "Failed", "msg" => "Not added to Favorites");
                $this->response($this->json($error), 400);
            }
        } else if ($loginchkfav->num_rows == 1) {

            $row = $loginchkfav->fetch_assoc();
            $activefav = $row['IsActive'];
            $deletefav = $row['IsDelete'];
            $favid = $row['Fav_id'];
            if ($activefav == '1' && $deletefav == '0') {
                $queryupdatefavorites = "update ozin_favorites  set IsActive='0' , IsDelete='1',ModifiedDate=now() where Fav_videoid='" . $videoid . "' and Fav_userid='" . $userid . "' and Fav_id='" . $favid . "'";
                $updatefavo = $this->mysqli->query($queryupdatefavorites) or die($this->mysqli->error . __LINE__);

                if ($updatefavo = true) {
                    $error = array('status' => 'Removed', "msg" => "Video Successfully Removed From Favorites!");
                    $this->response($this->json($error), 200);
                } else {
                    $error = array('status' => "Failed", "msg" => "Not remove to Favorites");
                    $this->response($this->json($error), 400);
                }
            } else {

                $queryupdatefavorites = "update ozin_favorites  set IsActive='1' , IsDelete='0',ModifiedDate=now() where Fav_videoid='" . $videoid . "' and Fav_userid='" . $userid . "' and Fav_id='" . $favid . "'";
                $updatefavo = $this->mysqli->query($queryupdatefavorites) or die($this->mysqli->error . __LINE__);

                if ($updatefavo = true) {
                    $error = array('status' => 'Updated', "msg" => "Video Successfully Updated to Favorites!");
                    $this->response($this->json($error), 200);
                } else {
                    $error = array('status' => "Failed", "msg" => "Not remove to Favorites");
                    $this->response($this->json($error), 400);
                }
            }
        }
//        $error = array('status' => "Exist", "msg" => "Video Already exist in your  Favorites");
//        $this->response($this->json($error), 200);
    }

    public function addtolikevideo() {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $userid = $this->_request['userid'];
        $videoid = $this->_request['videoid'];

        $querychklike = "select * from ozin_likevideo olike where olike.Like_videoid= '" . $videoid . "' and olike.Like_userid='" . $userid . "'";
        $loginchklike = $this->mysqli->query($querychklike) or die($this->mysqli->error . __LINE__);
//        echo$loginchkfav->num_rows;
        if ($loginchklike->num_rows == 0) {

            $queryinsertlikes = "INSERT INTO ozin_likevideo ( Like_videoid, Like_userid, IsActive,IsDelete , CreatedDate,ModifiedDate) "
                    . "VALUES ('$videoid', '$userid', '1','0',now(),now())";
            $insertlike = $this->mysqli->query($queryinsertlikes) or die($this->mysqli->error . __LINE__);

            if ($insertlike = true) {
                $error = array('status' => 'Inserted', "msg" => "Successfully Liked this Video !");
                $this->response($this->json($error), 200);
            } else {
                $error = array('status' => "Failed", "msg" => "Not Liked!");
                $this->response($this->json($error), 400);
            }
        } else if ($loginchklike->num_rows == 1) {

            $row = $loginchklike->fetch_assoc();
            $activelike = $row['IsActive'];
            $deletelike = $row['IsDelete'];
            $likeid = $row['Like_id'];
            if ($activelike == '1' && $deletelike == '0') {
                $queryupdatelikes = "update ozin_likevideo  set IsActive='0' , IsDelete='1',ModifiedDate=now() where Like_videoid='" . $videoid . "' and Like_userid='" . $userid . "' and Like_id='" . $likeid . "'";
                $updatelike = $this->mysqli->query($queryupdatelikes) or die($this->mysqli->error . __LINE__);

                if ($updatelike = true) {
                    $error = array('status' => 'Removed', "msg" => " Successfully Unliked Video!");
                    $this->response($this->json($error), 200);
                } else {
                    $error = array('status' => "Failed", "msg" => "Not Liked");
                    $this->response($this->json($error), 400);
                }
            } else {

                $queryupdatelikes = "update ozin_likevideo  set IsActive='1' , IsDelete='0',ModifiedDate=now() where Like_videoid='" . $videoid . "' and Like_userid='" . $userid . "' and Like_id='" . $likeid . "'";
                $updatelike = $this->mysqli->query($queryupdatelikes) or die($this->mysqli->error . __LINE__);

                if ($updatelike = true) {
                    $error = array('status' => 'Updated', "msg" => "Successfully Liked Video");
                    $this->response($this->json($error), 200);
                } else {
                    $error = array('status' => "Failed", "msg" => "Not Liked");
                    $this->response($this->json($error), 400);
                }
            }
        }
//        $error = array('status' => "Exist", "msg" => "Video Already exist in your  Favorites");
//        $this->response($this->json($error), 200);
    }

    public function checkfavorite() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $userid = $this->_request['userid'];
        $videoid = $this->_request['videoid'];

        $querychkfavo = "select * from ozin_favorites ofav where ofav.Fav_videoid= '" . $videoid . "' and ofav.Fav_userid='" . $userid . "'";
        $loginchkfav = $this->mysqli->query($querychkfavo) or die($this->mysqli->error . __LINE__);
        if ($loginchkfav->num_rows > 0) {
            $result = array();
            while ($row = $loginchkfav->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function checklikevideo() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $userid = $this->_request['userid'];
        $videoid = $this->_request['videoid'];

        $querychklike = "select * from ozin_likevideo olike where olike.Like_videoid= '" . $videoid . "' and olike.Like_userid='" . $userid . "'";
        $loginchklike = $this->mysqli->query($querychklike) or die($this->mysqli->error . __LINE__);
        if ($loginchklike->num_rows > 0) {
            $result = array();
            while ($row = $loginchklike->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function addcomment() {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $name = $this->_request['username'];
        $email = $this->_request['email'];
        $videoid = $this->_request['videoid'];
        $userid = $this->_request['userid'];
        $description = $this->_request['description'];
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $queryinsertcomment = "INSERT INTO ozin_comment (VideoId,Userid, CommentName, CommentEmail, CommentContent,CreatedDate ,ModifiedDate,IsActive,IsApprove,IsDelete) VALUES ('$videoid','$userid','$name', '$email', '$description',now(),now(),'1','0','0')";
            $insertcomment = $this->mysqli->query($queryinsertcomment) or die($this->mysqli->error . __LINE__);
            if ($insertcomment = true) {
                $error = array('status' => $insertcomment, "msg" => "Video Added to Favorites Successfully !");
                $this->response($this->json($error), 200);
            }
        }
    }

    public function getcommentvideo() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $videoid = $this->_request['videoid'];
        $ordervalue = $this->_request['ordervalue'];
        if ($videoid != NULL) {
            $video = " AND ocomm.VideoId=" . $videoid . " ";
        } else {
            $video = " And 1=1";
        }
        if ($ordervalue != NULL) {
            if ($ordervalue == 1) {
                $order = " order by ocomm.ModifiedDate desc";
            } else if ($ordervalue == 2) {
                $order = " order by ocomm.ModifiedDate ";
            }
        } else {
            $order = " order by ocomm.ModifiedDate ";
        }

        $query = "select * from ozin_comment ocomm where ocomm.IsActive=1 and ocomm.IsApprove=1 and ocomm.IsDelete=0  " . $video . " " . $order;
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function sortcommentvideo() {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $videoid = $this->_request['videoid'];
        $ordervalue = $this->_request['ordervalue'];
        if ($videoid != NULL) {
            $video = " AND ocomm.VideoId=" . $videoid . " ";
        } else {
            $video = " And 1=1";
        }
        if ($ordervalue != NULL) {
            if ($ordervalue == 1) {
                $order = " order by ocomm.ModifiedDate ";
            } else if ($ordervalue == 2) {
                $order = " order by ocomm.ModifiedDate desc ";
            }
        } else {
            $order = " order by ocomm.ModifiedDate ";
        }

        $query = "select * from ozin_comment ocomm where ocomm.IsActive=1 and ocomm.IsApprove=1 and ocomm.IsDelete=0  " . $video . " " . $order;
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function gettrendingvideo() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $query = "SELECT ct.CategoryId,ct.ParentCategoryId,ct.CategoryName, ozvideo.Videotitle,ozvideo.VideoId,ozvideo.VideoName,
 ozvideo.ImagePathThumb,st.SubCategoryName,ozvidviw.ReView,ozvidviw.EdView,GREATEST(ozvidviw.ReView, ozvidviw.EdView) as actualview,ozvideo.VideoDuration
FROM category ct
INNER JOIN subcategory st ON ct.CategoryId=st.CategoryId
INNER JOIN ozin_video ozvideo ON ct.CategoryId=ozvideo.CategoryId
LEFT JOIN ozin_videoviews ozvidviw ON ozvideo.VideoId=ozvidviw.VideoId
WHERE ct.IsActive=1 AND st.IsActive=1 AND ozvideo.IsActive=1 AND ozvideo.TrendingNow=1
GROUP BY ozvideo.VideoId limit 9";
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function addvideoviews() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $videoid = $this->_request['videoid'];
        $sqlcheckvideoid = "select * from ozin_videoviews ovideo where ovideo.VideoId=" . $videoid;
        $checkvideoid = $this->mysqli->query($sqlcheckvideoid) or die($this->mysqli->error . __LINE__);
        if ($checkvideoid->num_rows == 0) {
            $queryinsertvideoview = "INSERT INTO ozin_videoviews ( VideoId, ReView, EdView,CreatedDate ,ModifiedDate, IsActive, IsDelete) "
                    . "VALUES ('$videoid', '1', '0',now(),now(), '1',  '0')";

            $insertvideoview = $this->mysqli->query($queryinsertvideoview) or die($this->mysqli->error . __LINE__);
            if ($insertvideoview) {
                $error = array('status' => "1", "msg" => "Video Views Added Successfully");
                $this->response($this->json($error), 200);
            } else {
                $error = array('status' => "2", "msg" => "Something Went Wrong!");
                $this->response($this->json($error), 204);  // If no records "No Conte"
            }
        } else {

            $existingvideoviews = array();
            while ($rowvideoview = $checkvideoid->fetch_assoc()) {
                $existingvideoviews[] = $rowvideoview['ReView'];
            }
            $existvidcount = implode(" ", $existingvideoviews);
            $existvidcount = $existvidcount + 1;

            $queryupdatevideoview = "UPDATE ozin_videoviews set ReView='$existvidcount' , ModifiedDate= now() WHERE VideoId='$videoid'";
            $updatevideoview = $this->mysqli->query($queryupdatevideoview) or die($this->mysqli->error . __LINE__);
            if ($updatevideoview) {
                $error = array('status' => '1', "msg" => "Video Views Updated  Successfully");
                $this->response($this->json($error), 200);
            } else {
                $error = array('status' => '2', "msg" => "Something Went Wrong!");
                $this->response($this->json($error), 204);  // If no records "No Conte"
            }
        }
    }

    private function releatedvideo() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $categoryid = $this->_request['categoryid'];
        if ($categoryid != NULL) {
            $category = " AND ovideo.CategoryId=" . $categoryid . " ";
        } else {
            $category = " And 1=1";
        }

        $subcategoryid = $this->_request['subcategoryid'];
        if ($subcategoryid != NULL) {
            $subcategory = " AND ovideo.SubCategoryId not in ('" . $subcategoryid . "') ";
        } else {
            $subcategory = " And 1=1";
        }

        $limitval = $this->_request['limit'];
        if ($limitval != NULL) {
            $limit = " limit $limitval ";
        } else {
            $limit = " ";
        }

        $queryrelatedvideo = "SELECT ovideo.VideoId,ovideo.TagId,ovideo.Videotitle,ovideo.ImageName,ovideo.CategoryId, ovideo.ImagePathThumb,ovideo.SubCategoryId,ovideo.ModifiedDate,DATEDIFF(CURDATE(), ovideo.ModifiedDate) as uploadday,ozvidviw.ReView,ozvidviw.EdView,GREATEST(ozvidviw.ReView, ozvidviw.EdView) as actualview,ovideo.Description,ovideo.VideoDuration
                  FROM ozin_video ovideo 
                  LEFT JOIN ozin_videoviews ozvidviw ON ovideo.VideoId=ozvidviw.VideoId
                  WHERE ovideo.IsActive=1 AND ovideo.IsDelete=0" . $category . " " . $subcategory . " $limit";
//        echo $queryrelatedvideo;
        $getrelatedvideo = $this->mysqli->query($queryrelatedvideo) or die($this->mysqli->error . __LINE__);
        if ($getrelatedvideo->num_rows > 0) {
            $result = array();
            while ($row = $getrelatedvideo->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function upcomingvideo() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $videoid = $this->_request['videoid'];
        if ($videoid != NULL) {
            $video = " AND ovideo.VideoId not in ('" . $videoid . "') ";
        } else {
            $video = " And 1=1";
        }
        $subcategoryid = $this->_request['subcategoryid'];

        $query = "SELECT ovideo.VideoId,ovideo.Videotitle,ovideo.CategoryId,ovideo.SubCategoryId,ovideo.TagId,ovideo.StartAddId,
                  ovideo.EndAddId,ovideo.VideoName,ovideo.VideoPath,ovideo.ImageName,ovideo.ImagePathThumb,ovideo.Description,
                  ozvidviw.VideoId  as viewvideoid,ozvidviw.ReView,ozvidviw.EdView, GREATEST(ozvidviw.ReView, ozvidviw.EdView) AS VideoViews, ovideo.TagId,ovideo.VideoDuration
                  FROM ozin_video ovideo LEFT JOIN ozin_videoviews ozvidviw ON ovideo.VideoId=ozvidviw.VideoId
                  LEFT JOIN tags tg on ovideo.TagId=tg.TagId WHERE ovideo.IsActive=1" . $video . " ORDER BY FIELD (ovideo.SubCategoryId, $subcategoryid) desc limit 6";
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function watchedvideos() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $userid = $this->_request['userid'];
        $videoid = $this->_request['videoid'];
        $sqlwatchedvideos = "select * from ozin_watchedvideos watvid where watvid.UserId=" . $userid;
        $checkuserexist = $this->mysqli->query($sqlwatchedvideos) or die($this->mysqli->error . __LINE__);
        if ($checkuserexist->num_rows == 0) {
            $queryinsertuserwatchvideo = "INSERT INTO ozin_watchedvideos (  UserId,VideoId,CreatedDate ,ModifiedDate, IsActive, IsDelete) "
                    . "VALUES ('$userid','$videoid' ,now(),now(), '1',  '0')";

            $insertwatchvideo = $this->mysqli->query($queryinsertuserwatchvideo) or die($this->mysqli->error . __LINE__);
            if ($insertwatchvideo) {
                $error = array('status' => $queryinsertuserwatchvideo, "msg" => "watch video added successfully!");
                $this->response($this->json($error), 200);
            } else {
                $error = array('status' => "2", "msg" => "Something Went Wrong!");
                $this->response($this->json($error), 204);  // If no records "No Conte"
            }
        } else {
            $existingwathcvideoarray = array();
            while ($rowwatchvideo = $checkuserexist->fetch_assoc()) {
                $existingwathcvideoarray[] = $rowwatchvideo['VideoId'];
            }
            $existingwathcarraytostring = implode("  ", $existingwathcvideoarray);
            $existingwathchstringtoarray = explode(",", $existingwathcarraytostring);
            $existingvideocount = count($existingwathchstringtoarray);
            if (in_array($videoid, $existingwathchstringtoarray)) {
                $error = array('status' => '7', "msg" => "Video Already Watched!");
                $this->response($this->json($error), 200);
            } else {
                if ($existingvideocount > 30) {
                    array_shift($existingwathchstringtoarray);
                    array_push($existingwathchstringtoarray, $videoid);
                    $existingwathcharraytostr = implode(",", $existingwathchstringtoarray);
                    $queryupdatewatchvideo = "UPDATE ozin_watchedvideos set VideoId='$existingwathcharraytostr' , ModifiedDate= now() where UserId=" . $userid;
                    $updatewatchvideo = $this->mysqli->query($queryupdatewatchvideo) or die($this->mysqli->error . __LINE__);
                    if ($updatewatchvideo) {
                        $error = array('status' => '3', "msg" => "Watch Video Updated Successfully");
                        $this->response($this->json($error), 200);
                    } else {
                        $error = array('status' => '4', "msg" => "Something Went Wrong!");
                        $this->response($this->json($error), 204);  // If no records "No Conte"
                    }
                } else {
                    array_push($existingwathchstringtoarray, $videoid);
                    $existingwathcharraytostr = implode(",", $existingwathchstringtoarray);
                    $queryupdatewatchvideo = "UPDATE ozin_watchedvideos set VideoId='$existingwathcharraytostr' , ModifiedDate= now() where UserId=" . $userid;
                    $updatewatchvideo = $this->mysqli->query($queryupdatewatchvideo) or die($this->mysqli->error . __LINE__);
                    if ($updatewatchvideo) {
                        $error = array('status' => '5', "msg" => "Watch Video Updated Successfully");
                        $this->response($this->json($error), 200);
                    } else {
                        $error = array('status' => '6', "msg" => "Something Went Wrong!");
                        $this->response($this->json($error), 204);  // If no records "No Conte"
                    }
                }
            }
        }
    }

    public function getwatchedvideos() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $userid = $this->_request['userid'];
        $sqlgetwatchvideos = "select * from ozin_watchedvideos watvid where watvid.UserId=" . $userid;
        $getwatchvideo = $this->mysqli->query($sqlgetwatchvideos) or die($this->mysqli->error . __LINE__);
        if ($getwatchvideo->num_rows > 0) {
            $result = array();
            while ($row = $getwatchvideo->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function notificationsuser() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $userid = $this->_request['userid'];
        $sqlnotificationuser = "select * from ozin_notifyvideos oznoti where oznoti.IsActive=1 and oznoti.UserId=" . $userid;
        $getnotification = $this->mysqli->query($sqlnotificationuser) or die($this->mysqli->error . __LINE__);
        if ($getnotification->num_rows > 0) {
            $result = array();
            while ($row = $getnotification->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function visitors() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $sqlwebsiteviewers = "select * from websiteviewers webview ";
        $checkviewcount = $this->mysqli->query($sqlwebsiteviewers) or die($this->mysqli->error . __LINE__);
        if ($checkviewcount->num_rows == 0) {
            $queryinsertvisitorview = "INSERT INTO websiteviewers (  VisitorCount,CreatedDate ,ModifiedDate, IsActive, IsDelete) "
                    . "VALUES ('1', now(),now(), '1',  '0')";

            $insertvisitorview = $this->mysqli->query($queryinsertvisitorview) or die($this->mysqli->error . __LINE__);
            if ($insertvisitorview) {
                $error = array('status' => "1", "msg" => "Website Viewes added Successfully!");
                $this->response($this->json($error), 200);
            } else {
                $error = array('status' => "2", "msg" => "Something Went Wrong!");
                $this->response($this->json($error), 204);  // If no records "No Conte"
            }
        } else {

            $existingvideoviews = array();
            while ($rowvideoview = $checkviewcount->fetch_assoc()) {
                $existingvideoviews[] = $rowvideoview['VisitorCount'];
            }
            $existvidcount = implode(" ", $existingvideoviews);
            $existvidcount = $existvidcount + 1;

            $queryupdatevisitorview = "UPDATE websiteviewers set VisitorCount='$existvidcount' , ModifiedDate= now()";
            $updatevisitorview = $this->mysqli->query($queryupdatevisitorview) or die($this->mysqli->error . __LINE__);
            if ($updatevisitorview) {
                $error = array('status' => '1', "msg" => "Video Views Updated  Successfully");
                $this->response($this->json($error), 200);
            } else {
                $error = array('status' => '2', "msg" => "Something Went Wrong!");
                $this->response($this->json($error), 204);  // If no records "No Conte"
            }
        }
    }

    public function currentwishes() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }

        $startdate = $this->_request['startdate'];
        if ($startdate != NULL) {
            $datestart = " AND ozadwish.StartDate= '" . $startdate . "'";
        } else {
            $datestart = " And 1=1";
        }

        $adminwishid = $this->_request['adminwishid'];
        if ($adminwishid != NULL) {
            $adminwish = " AND ozadwish.AdminwishId= '" . $adminwishid . "'";
        } else {
            $adminwish = " And 1=1";
        }

        $notadminwishid = $this->_request['notinadminwishid'];
        if ($notadminwishid != NULL) {
            $notadminwish = " AND ozadwish.AdminwishId not in ('" . $notadminwishid . "')";
        } else {
            $notadminwish = " And 1=1";
        }

        $occasionid = $this->_request['occasion'];
        if ($occasionid != NULL) {
            $occasion = " AND ozadwish.OccasionId= '" . $occasionid . "'";
//                        $occasion = " And 1=1";
        } else {
            $occasion = " And 1=1";
        }

        $groupby = $this->_request['groupby'];
        if ($groupby != NULL) {
            $group = " group by ozadwish.OccasionId ";
//                        $occasion = " And 1=1";
        } else {
            $group = " ";
        }

        $limitval = $this->_request['limit'];
        if ($limitval != NULL) {
            $limit = " limit $limitval ";
        } else {
            $limit = " ";
        }

        $query = "select * from ozin_adminwish ozadwish where ozadwish.IsActive=1 and ozadwish.IsDelete=0 and ozadwish.EndDate >= curdate() " . $datestart . " " . $adminwish . " " . $notadminwish . " " . $occasion . " " . $group . " order by ozadwish.StartDate " . $limit;
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function addtowishfavorites() {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $userid = $this->_request['userid'];
        $adminwishid = $this->_request['adminwishid'];

        $querychkwishfav = "select * from ozin_wishfavorites owishfav where   owishfav.WishFavAdminWishId='" . $adminwishid . "' and owishfav.WishFavUserId='" . $userid . "'";
        $loginchkwishfav = $this->mysqli->query($querychkwishfav) or die($this->mysqli->error . __LINE__);
        if ($loginchkwishfav->num_rows == 0) {

            $queryinsertwishfavorites = "INSERT INTO ozin_wishfavorites ( WishFavAdminWishId,WishFavUserId, IsActive,IsDelete , CreatedDate,ModifiedDate) "
                    . "VALUES ('$adminwishid', '$userid', '1','0',now(),now())";
            $insertwishfavo = $this->mysqli->query($queryinsertwishfavorites) or die($this->mysqli->error . __LINE__);

            if ($insertwishfavo = true) {
                $error = array('status' => 'Inserted', "msg" => "Wish added to favorites Successfully!");
                $this->response($this->json($error), 200);
            } else {
                $error = array('status' => "2", "msg" => "Not added to Favorites");
                $this->response($this->json($error), 400);
            }
        } else if ($loginchkwishfav->num_rows == 1) {
            $row = $loginchkwishfav->fetch_assoc();
            $activefav = $row['IsActive'];
            $deletefav = $row['IsDelete'];
            $wishfavid = $row['WishFavId'];

            if ($activefav == '1' && $deletefav == '0') {
                $queryupdatewishfavorites = "update ozin_wishfavorites  set IsActive='0' , IsDelete='1',ModifiedDate=now() where WishFavAdminWishId='" . $adminwishid . "' and WishFavUserId='" . $userid . "' and WishFavId='" . $wishfavid . "'";
                $updatewishfavo = $this->mysqli->query($queryupdatewishfavorites) or die($this->mysqli->error . __LINE__);

                if ($updatewishfavo = true) {
                    $error = array('status' => 'Removed', "msg" => "Wish   Successfully Removed From Favorites!");
                    $this->response($this->json($error), 200);
                } else {
                    $error = array('status' => "Failed", "msg" => "Not remove to Favorites");
                    $this->response($this->json($error), 400);
                }
            } else {
                $queryupdatewishfavorites = "update ozin_wishfavorites  set IsActive='1' , IsDelete='0',ModifiedDate=now() where WishFavAdminWishId='" . $adminwishid . "' and WishFavUserId='" . $userid . "' and WishFavId='" . $wishfavid . "'";
                $updatewishfavo = $this->mysqli->query($queryupdatewishfavorites) or die($this->mysqli->error . __LINE__);

                if ($updatewishfavo = true) {
                    $error = array('status' => 'Updated', "msg" => "Wish  Successfully Updated to Favorites!");
                    $this->response($this->json($error), 200);
                } else {
                    $error = array('status' => "Failed", "msg" => "Not remove to Favorites");
                    $this->response($this->json($error), 400);
                }
            }
        }
    }

    public function checkwishfavorite() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $userid = $this->_request['userid'];
        $adminwishid = $this->_request['adminwishid'];

        $querychkwishfav = "select * from ozin_wishfavorites owishfav where   owishfav.WishFavAdminWishId='" . $adminwishid . "' and owishfav.WishFavUserId='" . $userid . "'";
        $loginchkwishfav = $this->mysqli->query($querychkwishfav) or die($this->mysqli->error . __LINE__);
        if ($loginchkwishfav->num_rows > 0) {
            $result = array();
            while ($row = $loginchkwishfav->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function getalladvert() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $advertid = $this->_request['advertid'];
        if ($advertid != NULL) {
            $advertisement = " AND ozadv.AdvertId in ('" . $advertid . "')";
        } else {
            $advertisement = " And 1=1";
        }

        $notadvertid = $this->_request['notadvertid'];
        if ($notadvertid != NULL) {
            $notadvertisement = " AND ozadv.AdvertId not in ('" . $notadvertid . "')";
        } else {
            $notadvertisement = " And 1=1";
        }

        $limit = $this->_request['limit'];
        if ($limit != NULL) {
            $limitset = "limit $limit";
        } else {
            $limitset = " ";
        }

        $query = "select * from ozin_advert ozadv 
inner join category ct on ct.CategoryId=ozadv.CategoryId
inner join subcategory subt on subt.SubCategoryId=ozadv.SubCategoryId
where ozadv.IsActive=1 and ozadv.IsDelete=0 " . $advertisement . " " . $notadvertisement . " " . $limitset;
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function foundrows() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $query = "SELECT FOUND_ROWS()";

        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function getcontactreason() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $query = "select * from ozin_contactreason ozcontreas where ozcontreas.IsActive=1 and ozcontreas.IsDelete=0";

        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function getlivevideo() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $currenttime = $this->_request['currenttime'];
        $todaydate = $this->_request['todaydate'];

        if ($todaydate != NULL) {
            $schedule = "  mastschd.ScheduleDate = ('" . $todaydate . "')";
        } else {
            $schedule = '  1=1';
        }
        if ($currenttime != NULL) {
            $current = "  AND (schlist.Starttime <= '" . $currenttime . "' and  schlist.EndTime > '" . $currenttime . "' )";
        } else {
            $current = '  1=1';
        }

        $sql = "SELECT TIMEDIFF(schlist.EndTime,schlist.Starttime) as videoduration,schlist.* ,mastschd.*,GREATEST(liveviews.ReView, liveviews.EdView) AS VideoViews FROM ozin_schedulelist schlist
                INNER JOIN ozin_masterschedule mastschd ON mastschd.MasterscheduleId = schlist.MasterscheduleId
                 Left join ozin_livetvvideoviews liveviews on schlist.SchedulelistId=liveviews.LivetvSchdeuleId
                WHERE " . $schedule . " " . $current . " and schlist.IsActive=1 and schlist.IsDelete=0 order by schlist.SchedulelistId ";
//       echo $sql;
        $r = $this->mysqli->query($sql) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {

            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function getliveupcomingvideo() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $currenttime = $this->_request['currenttime'];
        $todaydate = $this->_request['todaydate'];
        $limit = $this->_request['limit'];

        if ($todaydate != NULL) {
            $schedule = "  mastschd.ScheduleDate = ('" . $todaydate . "')";
        } else {
            $schedule = '  1=1';
        }
        if ($currenttime != NULL) {
            $current = "  AND (schlist.Starttime > '" . $currenttime . "'  )";
        } else {
            $current = '  AND 1=1';
        }
        if ($limit != NULL) {
            $limitset = " limit " . $limit;
        } else {
            $limitset = ' ';
        }


        $sql = "SELECT TIMEDIFF(schlist.EndTime,schlist.Starttime) as videoduration,schlist.* ,mastschd.*,GREATEST(liveviews.ReView, liveviews.EdView) AS VideoViews FROM ozin_schedulelist schlist
                INNER JOIN ozin_masterschedule mastschd ON mastschd.MasterscheduleId = schlist.MasterscheduleId
                Left join ozin_livetvvideoviews liveviews on schlist.SchedulelistId=liveviews.LivetvSchdeuleId
                WHERE " . $schedule . " " . $current . " and schlist.IsActive=1 and schlist.IsDelete=0 order by schlist.SchedulelistId " . $limitset;
//       echo $sql;
        $r = $this->mysqli->query($sql) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {

            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function addtofavoriteslivevideo() {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $userid = $this->_request['userid'];
        $scheduleid = $this->_request['scheduleid'];

        $querychkfavo = "select * from ozin_livefavorites ofav where ofav.LiveFav_scheduleid= '" . $scheduleid . "' and ofav.LiveFav_userid='" . $userid . "'";
        $loginchkfav = $this->mysqli->query($querychkfavo) or die($this->mysqli->error . __LINE__);
//        echo$loginchkfav->num_rows;
        if ($loginchkfav->num_rows == 0) {

            $queryinsertfavorites = "INSERT INTO ozin_livefavorites ( LiveFav_scheduleid, LiveFav_userid, IsActive,IsDelete , CreatedDate,ModifiedDate) "
                    . "VALUES ('$scheduleid', '$userid', '1','0',now(),now())";
            $insertfavo = $this->mysqli->query($queryinsertfavorites) or die($this->mysqli->error . __LINE__);

            if ($insertfavo = true) {
                $error = array('status' => 'Inserted', "msg" => "Schedule Added to Favorites Successfully !");
                $this->response($this->json($error), 200);
            } else {
                $error = array('status' => "Failed", "msg" => "Not Added to Favorites");
                $this->response($this->json($error), 400);
            }
        } else if ($loginchkfav->num_rows == 1) {

            $row = $loginchkfav->fetch_assoc();
            $activefav = $row['IsActive'];
            $deletefav = $row['IsDelete'];
            $favid = $row['LiveFav_id'];
            if ($activefav == '1' && $deletefav == '0') {
                $queryupdatefavorites = "update ozin_livefavorites  set IsActive='0' , IsDelete='1',ModifiedDate=now() where LiveFav_scheduleid='" . $scheduleid . "' and LiveFav_userid='" . $userid . "' and LiveFav_id='" . $favid . "'";
                $updatefavo = $this->mysqli->query($queryupdatefavorites) or die($this->mysqli->error . __LINE__);

                if ($updatefavo = true) {
                    $error = array('status' => 'Removed', "msg" => "Schedule Successfully Removed From Favorites!");
                    $this->response($this->json($error), 200);
                } else {
                    $error = array('status' => "Failed", "msg" => "Not remove to Favorites");
                    $this->response($this->json($error), 400);
                }
            } else {

                $queryupdatefavorites = "update ozin_livefavorites  set IsActive='1' , IsDelete='0',ModifiedDate=now() where LiveFav_scheduleid='" . $scheduleid . "' and LiveFav_userid='" . $userid . "' and LiveFav_id='" . $favid . "'";
                $updatefavo = $this->mysqli->query($queryupdatefavorites) or die($this->mysqli->error . __LINE__);

                if ($updatefavo = true) {
                    $error = array('status' => 'Updated', "msg" => "Schedule Successfully Updated to Favorites!");
                    $this->response($this->json($error), 200);
                } else {
                    $error = array('status' => "Failed", "msg" => "Not remove to Favorites");
                    $this->response($this->json($error), 400);
                }
            }
        }
//        $error = array('status' => "Exist", "msg" => "Video Already exist in your  Favorites");
//        $this->response($this->json($error), 200);
    }

    public function checklivetvfavorite() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $userid = $this->_request['userid'];
        $scheduleid = $this->_request['scheduleid'];

        $querychkfavo = "select * from ozin_livefavorites ofav where ofav.LiveFav_scheduleid= '" . $scheduleid . "' and ofav.LiveFav_userid='" . $userid . "'";
        $loginchkfav = $this->mysqli->query($querychkfavo) or die($this->mysqli->error . __LINE__);
        if ($loginchkfav->num_rows > 0) {
            $result = array();
            while ($row = $loginchkfav->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function addvideoviewslivetv() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $scheduleid = $this->_request['scheduleid'];
        $sqlcheckvideoid = "select * from ozin_livetvvideoviews ovideo where ovideo.LivetvSchdeuleId=" . $scheduleid;
        $checkvideoid = $this->mysqli->query($sqlcheckvideoid) or die($this->mysqli->error . __LINE__);
        if ($checkvideoid->num_rows == 0) {
            $queryinsertvideoview = "INSERT INTO ozin_livetvvideoviews ( LivetvSchdeuleId, ReView, EdView,CreatedDate ,ModifiedDate, IsActive, IsDelete) "
                    . "VALUES ('$scheduleid', '1', '0',now(),now(), '1',  '0')";

            $insertvideoview = $this->mysqli->query($queryinsertvideoview) or die($this->mysqli->error . __LINE__);
            if ($insertvideoview) {
                $error = array('status' => "1", "msg" => "Live Tv Video Views Added Successfully");
                $this->response($this->json($error), 200);
            } else {
                $error = array('status' => "2", "msg" => "Something Went Wrong!");
                $this->response($this->json($error), 204);  // If no records "No Conte"
            }
        } else {

            $existingvideoviews = array();
            while ($rowvideoview = $checkvideoid->fetch_assoc()) {
                $existingvideoviews[] = $rowvideoview['ReView'];
            }
            $existvidcount = implode(" ", $existingvideoviews);
            $existvidcount = $existvidcount + 1;

            $queryupdatevideoview = "UPDATE ozin_livetvvideoviews set ReView='$existvidcount' , ModifiedDate= now() WHERE LivetvSchdeuleId='$scheduleid'";
            $updatevideoview = $this->mysqli->query($queryupdatevideoview) or die($this->mysqli->error . __LINE__);
            if ($updatevideoview) {
                $error = array('status' => '1', "msg" => "Video Views Updated  Successfully");
                $this->response($this->json($error), 200);
            } else {
                $error = array('status' => '2', "msg" => "Something Went Wrong!");
                $this->response($this->json($error), 204);  // If no records "No Conte"
            }
        }
    }

    public function getlivefavvideos() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $userid = $this->_request['userid'];
        $currenttime = $this->_request['currenttime'];
        $limit = $this->_request['limit'];
        $offset = $this->_request['offset'];
        if ($limit != NULL) {
            if ($offset != NULL) {
                $limitset = " limit " . $limit . "," . $offset;
            } else {
                $limitset = " limit " . $limit;
            }
        } else {
            $limitset = ' ';
        }
        $sqlgetlivefavvideos = "SELECT TIMEDIFF(schlist.EndTime,schlist.Starttime) AS videoduration,schlist.*,mastschd.*, GREATEST(liveviews.ReView, liveviews.EdView) AS VideoViews
                                FROM ozin_schedulelist schlist
                                INNER JOIN ozin_masterschedule mastschd ON mastschd.MasterscheduleId = schlist.MasterscheduleId
                                LEFT JOIN ozin_livetvvideoviews liveviews ON schlist.SchedulelistId=liveviews.LivetvSchdeuleId
                                INNER JOIN ozin_livefavorites livefav ON schlist.SchedulelistId=livefav.LiveFav_scheduleid
                                WHERE ((mastschd.ScheduleDate = DATE(NOW()) AND (schlist.Starttime >= '" . $currenttime . "')) OR (mastschd.ScheduleDate > DATE(NOW()))) AND 
                                 schlist.IsActive=1 AND schlist.IsDelete=0 AND livefav.LiveFav_userid='" . $userid . "' AND livefav.IsActive=1 AND livefav.IsDelete=0
                                ORDER BY schlist.SchedulelistId" . $limitset;
        $getlivefavvideo = $this->mysqli->query($sqlgetlivefavvideos) or die($this->mysqli->error . __LINE__);
        if ($getlivefavvideo->num_rows > 0) {
            $result = array();
            while ($row = $getlivefavvideo->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    // Pratik Wok 6/6/2017 Start

    public function job() {

        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $jobid = $this->_request['jobid'];
        if ($jobid != NULL) {
            $job = " AND oj.JobId=" . $jobid . " ";
        } else {
            $job = " And 1=1";
        }

        $query = "SELECT * from ozin_job as oj where oj.IsActive=1 AND oj.IsDelete=0" . $job;
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send job details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function applyjob() {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $jobid = $this->_request['jobid'];
        $jobname = $this->_request['jname'];
        $name = $this->_request['username'];
        $email = $this->_request['email'];
        $phone = $this->_request['phone'];
        $country = $this->_request['country'];
        // $file = $this->_FILES['uploadfile']['name'];
        $temp = explode(".", $_FILES["uploadfile"]["name"]);
        // date_default_timezone_set('Asia/Calcutta');         
        $newfilename = $jobname . date("Y-m-d_H_i_s.") . $name . '.' . end($temp);
        $file_tmpname = $_FILES["uploadfile"]["tmp_name"];
        $upload_dir = "../uploadresume/";
        $resumepath = $upload_dir . $newfilename;
        move_uploaded_file($file_tmpname, $resumepath);
        $subject = $this->_request['subject'];
        $details = $this->_request['details'];

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $queryinsertappliedjob = "INSERT INTO ozin_applyjob (JobId,JobUserId,JobUsername,JobEmail,JobContactno,JobCountry,JobResumePath,JobSubject,JobDetails,CreatedDate,ModifiedDate,IsActive,IsDelete) VALUES ('$jobid','$userid','$name', '$email', '$phone','$country','$resumepath','$subject','$details',now(),now(),'1','0')";
            $insertappliedjob = $this->mysqli->query($queryinsertappliedjob) or die($this->mysqli->error . __LINE__);
            if ($insertappliedjob = true) {

                $to = ADMIN_EMAIL; // this is your Email address
                $from = $email; // this is the sender's Email address
                $subject1 = "Form submission User";
                $subject2 = "Thank you for job application";
                $message = $this->objEmailer->jobnotifyadmin($jobname, $name, $email, $phone, $country, $subject, $details);
                $filename = $newfilename;
                $path = $upload_dir;
                $file = $resumepath;

                $content = file_get_contents($file);
                $content = chunk_split(base64_encode($content));

                // a random hash will be necessary to send mixed content
                $separator = md5(time());

                // carriage return type (RFC)
                $eol = "\r\n";
                // main header (multipart mandatory)
                $headers = "From:" . $from . $eol;
                $headers .= "To :" . $to . $eol;
                $headers .= "MIME-Version: 1.0" . $eol;
                $headers .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"" . $eol;
                $headers .= "Content-Transfer-Encoding: 7bit" . $eol;
                $headers .= "This is a MIME encoded message." . $eol;

                // message

                $body = "--" . $separator . $eol;
                $body .= "Content-type:text/html; charset=iso-8859-1" . $eol;
                $body .= "Content-Transfer-Encoding: 7bit" . $eol;
                $body .= $message . $eol;

                // attachment
                $body .= "--" . $separator . $eol;
                $body .= "Content-Type: application/octet-stream;  name=\"" . $filename . "\"" . $eol;
                $body .= "Content-Transfer-Encoding: base64" . $eol;
                $body .= "Content-Disposition: attachment" . $eol;
                $body .= $content . $eol;
                $body .= "--" . $separator . "--";

                $message2 = $this->objEmailer->jobnotifyuser($name, $jobname);
                $header = "From:" . $from . "\r\n";
                $header .= "To :" . $to . "\r\n";
                $header .= "MIME-Version: 1.0 " . "\r\n";
                $header .= "Content-type:text/html;charset=UTF-8" . "\r\n";


                //SEND Mail
//                 $sendmail = $this->objEmailer->sendgridEmail($to, $from, $subject1, $body);
//                 $sendmail2 = $this->objEmailer->sendgridEmail($from, $to, $subject2, $message2);

                $sendmail = mail($to, $subject1, $body, $headers);
                $sendmail2 = mail($from, $subject2, $message2, $header);
                if ($sendmail && $sendmail2) {
                    $error = array('status' => "1", "msg" => "Applied successfully");
                    $this->response($this->json($error), 200);
                } else {
                    $this->response('Something Went Wrong', 204); // If no records "No Conte
                }
            }
        } else {
            $this->response('', 204);
        }
    }

    //13/06/2017

    public function changepass() {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $userid = $this->_request['uid'];
        $currentpass = md5($this->_request['oldpass']);
        $newpass = md5($this->_request['newpass']);
        $chkpass = md5($this->_request['chkpass']);
        if ($userid) {
            $result = "SELECT * from user WHERE UserId= $userid ";
            $chk = $this->mysqli->query($result) or die($this->mysqli->error . __LINE__);
            $row = mysqli_fetch_array($chk);
            $dbpass = $row["UserPassword"];
            if ($currentpass == $dbpass) {
                $updatequery = "UPDATE user set UserPassword='$newpass' WHERE UserId='$userid'";
                $update = $this->mysqli->query($updatequery) or die($this->mysqli->error . __LINE__);
                if ($update = true) {
                    $error = array('status' => "1", "msg" => "Password Change successfully");
                    $this->response($this->json($error), 200);
                } else {
                    $this->response('failed to upadte Password', 200); // If no records "No Conte
                }
            } else {
                $this->response('Wrong old Password', 204); // If no records "No Conte
            }
        } else {
            $this->response('User somthing went wrong', 204); // If no records "No Conte
        }
    }

    public function occasion() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $query = "select * from ozin_occasion";
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send ocassion details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function wishing() {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $from = $this->_request['wish_from'];
        $to = $this->_request['wish_to'];
        $email = $this->_request['email'];
        $occasion = $this->_request['occasion'];
        $date = $this->_request['curdate'];
        $wishdate = date('Y-m-d ', strtotime($date));
        $message = $this->_request['message'];

        //uploade file
        // $file = $this->_FILES['uploadfile']['name'];
        $temp = explode(".", $_FILES["uploadfile"]["name"]);
        $type = $_FILES["uploadfile"]["type"];
        date_default_timezone_set('Asia/Calcutta');
        $newfilename = $from . date("Y-m-d_H_i_s") . '.' . end($temp);
        $file_tmpname = $_FILES["uploadfile"]["tmp_name"];
        $upload_dir = "../upload_wishes/";
        $wish_file_path = $upload_dir . $newfilename;
        move_uploaded_file($file_tmpname, $wish_file_path);



        $query = "INSERT INTO ozin_userwishes (Wishto,Wishfrom,WisherEmail,OccasionId,WishDate,Message,CreatedDate,ModifiedDate,IsActive,IsDelete,Haswish,File_Path,File_Type) VALUES ('$to','$from', '$email', '$occasion','$wishdate','$message',now(),now(),'1','0','0','$wish_file_path','$type')";
        $insertwish = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
        if ($insertwish = true) {
            $error = array('status' => "1", "msg" => "Wishes Send successfully");
            $this->response($this->json($error), 200);
        } else {
            $this->response('Something Went Wrong', 204); // If no records "No Conte
        }
    }

    public function contactus() {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $name = $this->_request['name'];
        $email = $this->_request['email'];
        $phone = $this->_request['phone'];
        $option = $this->_request['option'];
        $message = $this->_request['message'];

        $query = "INSERT INTO ozin_contactus (Name,Email,ContactNumber,Message,Reason,CreatedDate,ModifiedDate,IsActive,IsDelete) VALUES ('$name','$email', '$phone','$message',$option,now(),now(),'1','0')";
        $insertcontact = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
        if ($insertconatct = true) {

            //email

            $admin_email = ADMIN_EMAIL;
            $to = $email;
            $subject = "Ozin Contact";
            $from = "Ozin";
            $message1 = '<div marginwidth="0" marginheight="0">

    <div dir="ltr" style="background-color:#f5f5f5;margin:0;padding:70px 0 70px 0;width:100%">

        <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%"><tbody><tr>

                    <td align="center" valign="top">

                        <div>

                            <p style="margin-top:0"> <a href="' . SERVER_URL . '"><img src="' . SERVER_URL . 'img/logo.png" alt="" style="border:none;display:inline;font-size:14px;font-weight:bold;min-height:auto;line-height:100%;outline:none;text-decoration:none;text-transform:capitalize" class="CToWUd"></a></p>						</div>

                        <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color:#fdfdfd;border:1px solid #dcdcdc;border-radius:3px!important">

                            <tbody><tr>

                                    <td align="center" valign="top">



                                        <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color:#FDD10B;border-radius:3px 3px 0 0!important;color:#ffffff;border-bottom:0;font-weight:bold;line-height:100%;vertical-align:middle;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif"><tbody><tr>

                                                    <td style="padding:36px 48px;display:block">

                                                        <h1 style="color:#191919;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:30px;font-weight:300;line-height:150%;margin:0;text-align:left">Contact Us </h1>

                                                    </td>

                                                </tr></tbody></table>



                                    </td>

                                </tr>

                                <tr>

                                    <td align="center" valign="top">



                                        <table border="0" cellpadding="0" cellspacing="0" width="600"><tbody><tr><td valign="top" style="background-color:#fdfdfd">



                                                        <table border="0" cellpadding="20" cellspacing="0" width="100%"><tbody><tr>

                                                                    <td valign="top" style="padding:48px">

                                                                        <div style="color:#737373;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:14px;line-height:150%;text-align:left">





                                                                            <h2 style="color:#557da1;display:block;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:18px;font-weight:bold;line-height:130%;margin:16px 0 8px;text-align:left">Thanks....! </h2>

                                                                            <ul>

                                                                                <li>

                                                                                    Hi, ' . $name . '<br/> '
                    . '                                                                Thanks For Contact Us , We will get back to you soon....!

                                                                                </li>

                                                                            </ul>



                                                                        </div>

                                                                    </td>

                                                                </tr></tbody></table>



                                                    </td>

                                                </tr></tbody></table>



                                    </td>

                                </tr>

                                <tr>

                                    <td align="center" valign="top">



                                        <table border="0" cellpadding="10" cellspacing="0" width="600"><tbody><tr>

                                                    <td valign="top" style="padding:0">

                                                        <table border="0" cellpadding="10" cellspacing="0" width="100%"><tbody><tr>

                                                                    <td colspan="2" valign="middle" style="padding:0 48px 48px 48px;border:0;color:#99b1c7;font-family:Arial;font-size:12px;line-height:125%;text-align:center">

                                                                       <p>Thank You for Visiting <a href="' . SERVER_URL . '">Ozintv</a>.</p>

                                                                    </td>

                                                                </tr></tbody></table>

                                                    </td>

                                                </tr>

                                            </tbody>

                                        </table>



                                    </td>

                                </tr>

                            </tbody></table>

                    </td>

                </tr></tbody></table>

    </div>

</div>';
            $eol = "\r\n";
            $headers = "From:" . $from . "\r\n";
            $headers .= "To :" . $to . "\r\n";
            $headers .= "MIME-Version: 1.0 " . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
//                $headers .= "Content-type:text/html; charset=iso-8859-1" . $eol;
//                $headers .="Content-Transfer-Encoding: 7bit" . $eol;
//                 $headers.= "MIME-Version: 1.0 " . "\r\n";
//                 $headers.= "Content-type:text/html;charset=UTF-8" . "\r\n";

            $admin_message = "<div marginwidth='0' marginheight='0'>
	<div dir='ltr' style='background-color:#f5f5f5;margin:0;padding:70px 0 70px 0;width:100%'>
		<table border='0' cellpadding='0' cellspacing='0' height='100%' width='100%'>
		<tbody><tr>
			<td align='center' valign='top'>
			<div>
				<p style='margin-top:0'><img src='http://www.ozintv.com/img/logo.png' alt='' style='border:none;height: 80px;width: 250px;display:inline;font-size:14px;font-weight:bold;min-height:auto;line-height:100%;outline:none;text-decoration:none;text-transform:capitalize' class='CToWUd'></p>
			</div>

			<table border='0' cellpadding='0' cellspacing='0' width='600' style='background-color:#F2F2F2;border:1px solid #dcdcdc;border-radius:3px!important'>
			<tbody>
				<tr>
					<td align='center' valign='top'>
						<table border='0' cellpadding='0' cellspacing='0' width='600' style='background-color:#000000;border-radius:3px 3px 0 0!important;color:#ffffff;border-bottom:0;font-weight:bold;line-height:100%;vertical-align:middle;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif'>
							<tbody><tr>
								<td style='padding:36px 48px;display:block;background:#fdd10b;'>
									<h1 style='color:black;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:30px;font-weight:300;line-height:150%;margin:0;text-align:center'>Contact Us</h1>
								</td>
								
							</tr></tbody>
						</table>
					</td>
				</tr>
				
				<tr>
					<td>
						<p style='margin:0 0 0 45px;color:#737373;margin-top: 25px;display:block;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:18px;font-weight:bold;'>Dear Admin,<br><br></p>
						<p style='margin:0 0 0 45px;color:#737373;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:16px;'>There is a new contact request from the website. Please check the details below.<br></p>
					</td>
				</tr>
			
				<tr>
					<td align='center' valign='top'>
														
						<table border='0' cellpadding='0' cellspacing='0' width='600'>
							<tbody><tr>
								<td valign='top' style='background-color:#F2F2F2'>
																				
									<table border='0' cellpadding='20' cellspacing='0' width='100%'>
										<tbody><tr>
											<td valign='top' style='padding: 0px 53px 0px 45px;'>
												<div style='color:#737373;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:14px;line-height:150%;text-align:left'>
													<table cellspacing='0' cellpadding='6' style='width:100%;color:#737373;border:1px solid #e4e4e4;margin-top:25px;margin-bottom: 25px;' border='1'>
														<thead><tr>
															<th scope='col' style='text-align:left;color:#737373;border:1px solid #e4e4e4;padding:12px'>Name</th>
															<td scope='col' style='text-align:left;color:#737373;border:1px solid #e4e4e4;padding:12px'>$name</td>
																	</tr><tr>	
															<th scope='col' style='text-align:left;color:#737373;border:1px solid #e4e4e4;padding:12px'>Email</th>
															<td scope='col' style='text-align:left;color:#737373;border:1px solid #e4e4e4;padding:12px'>$email</td>
																	</tr><tr>
															<th scope='col' style='text-align:left;color:#737373;border:1px solid #e4e4e4;padding:12px'>Phone</th>
															<td scope='col' style='text-align:left;color:#737373;border:1px solid #e4e4e4;padding:12px'>$phone</td>
																	</tr><tr>	
															<th scope='col' style='text-align:left;color:#737373;border:1px solid #e4e4e4;padding:12px'>Reasone</th>
															<td scope='col' style='text-align:left;color:#737373;border:1px solid #e4e4e4;padding:12px'>$option</td>
																	</tr><tr>
															<th scope='col' style='text-align:left;color:#737373;border:1px solid #e4e4e4;padding:12px'>Message</th>
															<td scope='col' style='text-align:left;color:#737373;border:1px solid #e4e4e4;padding:12px'>$message</td>
														</tr></thead>
													</table>
												</div>
											</td>
										</tr></tbody>
									</table>
									
								</td>
							</tr></tbody>
						</table>
						
					</td>
				</tr>
				<tr>
					<td align='center' valign='top'>
						
						<table border='0' cellpadding='10' cellspacing='0' width='600'>
							<tbody><tr>
								<td valign='top' style='padding:0'>
									
									<table border='0' cellpadding='10' cellspacing='0' width='100%'>
										<tbody><tr>
											<td colspan='2' valign='middle' style='    padding: 0px 48px 48px 35px;border:0;color:#99b1c7;font-family:Arial;font-size:12px;line-height:125%;text-align:center'>
												<p>This e-mail was sent from contact us page : http://www.ozintv.com/contact-us/</p>
											</td>
										</tr></tbody>
									</table>
								</td>
							</tr></tbody>
						</table>
					
					</td>
				</tr>
			</tbody>
			</table>
			</td>
		</tr></tbody>
		</table>
	</div>
</div>";
            $headers1 = "From:" . $from . "\r\n";
            $headers1 .= "To :" . $admin_email . "\r\n";
            $headers1 .= "MIME-Version: 1.0 " . "\r\n";
            $headers1 .= "Content-type:text/html;charset=UTF-8" . "\r\n";



//            $sendgrid_mail = $this->objEmailer->sendgridEmail($to, $subject, $message, $from);
//            $sendgrid_mail = $this->objEmailer->sendgridEmail($admin_email, $subject, $admin_message, $from);
            $usermail = mail($to, $from, $message1, $headers);
            $adminmail = mail($admin_email, $from, $admin_message, $headers1);

            //


            $error = array('status' => "1", "msg" => "Thanks For Contact Us..!");
            $this->response($this->json($error), 200);
        } else {
            $this->response('Something Went Wrong', 204); // If no records "No Conte
        }
    }

    public function newsletter() {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $email = $this->_request['email'];

        $checkdata = "SELECT emailid FROM ozin_newsletter WHERE emailid='$email' ";
        $check = $this->mysqli->query($checkdata) or die($this->mysqli->error . __LINE__);
        $row = mysqli_num_rows($check);
        if ($row > 0) {
            $error = array('status' => "2", "msg" => "Email Address Already Exists..!");
            $this->response($this->json($error), 200);
        } else {
            $query = "INSERT INTO ozin_newsletter (emailid,IsActive,IsDelete,CreatedDate,ModifiedDate) VALUES ('$email','1','0',now(),now())";
            $insertnewsletter = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
            if ($insertnewsletter = true) {

                //Mailchimp coding
                // MailChimp API credentials
                $apiKey = '87b445974173f2a081c5f7fc01e1eb54-us16';
                $listID = '8ff2ad041a';

                // MailChimp API URL
                $memberID = md5(strtolower($email));
                $dataCenter = substr($apiKey, strpos($apiKey, '-') + 1);
                $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listID . '/members/' . $memberID;

                // member information
                $json = json_encode([
                    'email_address' => $email,
                    'status' => 'subscribed',
                        /* 'merge_fields'  => [
                          'FNAME'     => $fname,
                          'LNAME'     => $lname
                          ] */
                ]);

                // send a HTTP POST request with curl
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                $result = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                // store the status message based on response code
                if ($httpCode == 200) {
                    $error = array('status' => "1", "msg" => "You have successfully subscribed to Ozin...!");
                    $this->response($this->json($error), 200);
                } else {
                    $this->response('Something Went Wrong', 204);
                }

//                $error = array('status' => "1", "msg" => "Thanks..!");
//                $this->response($this->json($error), 200);
            } else {
                $this->response('Something Went Wrong', 204);
            }
        }
    }

    public function songscategory() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $query = "select * from songscategory socateg where socateg.IsActive=1 and socateg.IsDelete=0";
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send songs category details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function songsyear() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $query = "select * from songsyear soyear where soyear.IsActive=1 and soyear.IsDelete=0";
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send Songs Year details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function songrecommend() {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $name = $this->_request['name'];
        $songcategory = $this->_request['songcategory'];
        $songyear = $this->_request['songsyear'];
        if ($songyear != null) {
            $songyear = $songyear;
            $songyearid = "soyear.SongYearId=" . $songyear;
        } else {
            $songyear = null;
            $songyearid = " 1=1";
        }
        $movie = $this->_request['moviename'];
        $song = $this->_request['songname'];
        $singer = $this->_request['singer'];

        $songcatquery = "select * from songscategory sncat where sncat.SongCategoryId=" . $songcategory;
        $songcatsql = $this->mysqli->query($songcatquery) or die($this->mysqli->error . __LINE__);
        $songcatrow = $songcatsql->fetch_assoc();


        $songyearquery = "select * from songsyear soyear where " . $songyearid;
        $songyearsql = $this->mysqli->query($songyearquery) or die($this->mysqli->error . __LINE__);
        $songyearrow = $songyearsql->fetch_assoc();
//        print_r($songcatrow);

        $query = "INSERT INTO songrecommend (Username,SongsCategoryId,SongsYearId,MovieName,SongName,SingerName,IsActive,IsDelete,CreatedDate,ModifiedDate) VALUES ('$name','$songcategory', '$songyear','$movie','$song','$singer','1','0',now(),now())";
        $insertrecommendation = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
        if ($insertrecommendation = true) {
            if ($songyear != null) {
                $userbody = $this->objEmailer->sonrecommendemail($name, $songcatrow['SongCategoryName'], $songyearrow['SongYear'], $movie, $song, $singer);
            } else {
                $userbody = $this->objEmailer->sonrecommendemail($name, $songcatrow['SongCategoryName'], null, $movie, $song, $singer);
            }
            $userto = ADMIN_EMAIL;
            $userfrom = 'sachin@wdipl.com';
            $useremailsubject = 'Song Recommendation ';

            $headers = "From:" . $userfrom . "\r\n";
            $headers .= "To :" . $userto . "\r\n";
            $headers .= "MIME-Version: 1.0 " . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

            $sendmail = mail($userto, $useremailsubject, $userbody, $headers);
//            $sendmail=$this->objEmailer->sendgridEmail($userto, $userfrom, $useremailsubject, $userbody);
            if ($sendmail) {
                $error = array('status' => "1", "msg" => "Song Recommendation Successfully Done!");
                $this->response($this->json($error), 200);
            } else {
                $this->response('Something Went Wrong', 204); // If no records "No Conte
            }
        } else {
            $this->response('Something Went Wrong', 204); // If no records "No Conte
        }
    }

    public function views() {
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $query = "select GREATEST(webview.VisitorCount, webview.AdminCount) AS websiteview,webview.VisitorId from websiteviewers webview";
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

        if ($r->num_rows > 0) {
            $result = array();
            while ($row = $r->fetch_assoc()) {

                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send Songs Year details
        }
        $this->response('', 204); // If no records "No Content" status
    }

    public function Forgotpassword() {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        $email = $this->_request['loginemail'];
        if (!empty($email)) {
            $query = "select * from user where UserEmail='$email' and IsActive=1 and DelStatus=0";
            $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
            $row = $r->fetch_assoc();
            $useremail = $row['UserEmail'];
            if ($r->num_rows > 0) {
                $length = 8;
                $password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
                $updatepass = "update user set UserPassword='" . md5($password) . "' where UserEmail='$useremail'";
                $updtpass = $this->mysqli->query($updatepass) or die($this->mysqli->error . __LINE__);

                //  $result = $updtpass->fetch_assoc();
                $username = $row['UserFirstName'];
                $userpass = $password;

                $to = $email; // this is your Email address
                $from = ADMIN_EMAIL; // this is the sender's Email address
                $subject1 = "Set Your New Password.";
                $message1 = "<div style='background:aliceblue'> Hello" . $username . "\n\n Your New Password Is Set.You Can Now Login With your Email Address and Use Below Password.<br /> Your New Password is : " . $userpass . "<br /> login here :" . SERVER_URL . " <br /><br>Regards,<br> Admin</div>";
                $headers = "From:" . $from . "\r\n";
                $headers .= "To :" . $to . "\r\n";
                $headers .= "MIME-Version: 1.0 " . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $sendmail = mail($to, $subject1, $message1, $headers);

                if ($sendmail) {
                    $error = array('status' => "1", "msg" => "Mail Send successfully");
                    $this->response($this->json($error), 200);
                } else {
                    $this->response('Mail Not Send', 204); // If no records "No Conte
                }
            } else {
                $error = array('status' => "2", "msg" => "Wrong Email ID ");
                $this->response($this->json($error), 200);
            }
        } else {
            $this->response('Pls Check Email Id', 204);
        }
    }

    // Pratik Wok 6/6/2017 End

    public function jobEmailToFriend() {
//        $error = array('status' => "2", "msg" => "Wrong Email ID ");
//                $this->response($this->json($error), 200);

        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $sender_email = $this->_request['user_email'];
        $to_email = $this->_request['to_email'];
        $description = $this->_request['description'];
        $url = $this->_request['link'];

        if (!empty($sender_email) && !empty($to_email)) {
//            $error = array('status' => "2", "msg" => $url);
//                $this->response($this->json($error), 200);
            $body = $this->objEmailer->jobToFriend($description, $url);
//            $body = $description;
            $useremailsubject = 'Job Recommendation ';

            $headers = "From:" . $sender_email . "\r\n";
            $headers .= "To :" . $to_email . "\r\n";
            $headers .= "MIME-Version: 1.0 " . "\r\n";
            $headers .= 'Content-type: text/html;charset=UTF-8';
//            $headers.= "Content-Type: text/multipart; charset=UTF-8" . "\r\n";

            $sendmail = mail($to_email, $useremailsubject, $body, $headers);
            if ($sendmail) {
                $error = array('status' => "1", "msg" => "Job Recommendation Successfully Done!");
                $this->response($this->json($error), 200);
            } else {
                $this->response('Something Went Wrong', 204); // If no records "No Conte
            }
        }
    }

    /*
     * 	Encode array into JSON
     */

    private function json($data) {
        if (is_array($data)) {
            return json_encode($data);
        }
    }

}

// Initiiate Library

$api = new API;
$api->processApi();
?>
