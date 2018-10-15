<style>

    @media only screen and (max-width: 992px) {

        #navbar {display: none}

    }

</style>

<div class="sign-in-pg">
<?php

session_start();







require_once './config/siteconfig.php';











require_once './frontcommon/frontfunction.php';



$objfrntfunc = new frontfrontuitility();







//start get parent category api



$getparenturl = SERVER_URL . "services/parentcategory";



$sqlgetparentcategory = $objfrntfunc->getcurldata($getparenturl);



// End get parent category api

//start get  category api



$getcategoryurl = SERVER_URL . "services/category";



$sqlgetcategory = $objfrntfunc->getcurldata($getcategoryurl);



$categorycount = count($sqlgetcategory);



//print_r($sqlgetcategory);

// End get  category api

//start get  Banner api



$getbanner = SERVER_URL . "services/banner";



$sqlgetbanner = $objfrntfunc->getcurldata($getbanner);



// End get  Banner api

//start get trending videos api



$gettrendingvideo = SERVER_URL . "services/gettrendingvideo";



$sqlgettrendingvideo = $objfrntfunc->getcurldata($gettrendingvideo);



//print_r($sqlgettrendingvideo);

// End get trending videos api







require_once './frontcommon/header.php';

?>





<div class="responsive-heading">

    <h2><a href="<?= SERVER_URL ?>signin.php">Registration</a></h2>

</div>



<div class="container mt40">

    <form  id="frmregisterozinuser" name="frmregisterozinuser" class="form2">

        <div class="row">

            <div class="form-group">

                <input type="text" class="form-control"  id="username" name="username" placeholder="Name" />

            </div>

            <div class="form-group">

                <input type="email" class="form-control"  id="useremail" name="useremail" placeholder="Email " />

            </div>

            <div class="form-group">

                <input type="password" class="form-control"  id="passwd" name="password" placeholder="Password "  />

            </div>

            <div class="form-group">

                <input type="Password" class="form-control"  id="confirmpassword" name="confirmpassword" placeholder="Confirm Password"  />

            </div>

            <div class="form-group">

                <input type="text" class="form-control"  id="phoneno" name="phoneno" placeholder="Contact No"  />

            </div>

            <div class="form-group">

                <input type="text" class="form-control" id ="address" name="address" placeholder="Location">

            </div>

            <div class="form-group">

                <div class="radio">

<!--                    <label><input type="radio" name="optradio">Male</label>

                    <label><input type="radio" name="optradio">Female</label>-->

                    <label class="pdl0"><input type="radio"  name="gender" value="1" > <span class="lbl">Male</span></label>

                    <label><input type="radio" name="gender" value="2" > <span class="lbl">Female</span></label>

                </div>

            </div>

            <div class="form-group cln">

                <div class="date-pic">  

                    <div class="dob">

                        <div class="input-append date" id="dp5" data-date="" data-date-format="dd-mm-yyyy">

                            <input class="span2 add-on form-control" type="text" name="dob" placeholder="DD-MM-YYYY">

                            <span class="add-on"><i class="fa fa-calendar"></i></span> 



                        </div>

                    </div>

                </div>

            </div>





            <div class="form-group buttons-form">

                <input type="submit" id="submit" value="Register" />

            </div>





        </div>

    </form>

    <div class="form-group reg">

        <p class="text-center">Already Registered ? <a href="<?= SERVER_URL . "signin.php" ?>">Sign In</a></p>

    </div>

</div>


</div>


<script>



    $(document).ready(function () {

        

         $("#phoneno").intlTelInput();

         

        $('#frmregisterozinuser').validate({

            rules: {

                username: "required",

                useremail: {

                    required: true,

                    email: true



                },

                password: {

                    required: true







                },

                confirmpassword: {

                    required: true,

                    equalTo: "#passwd"



                },

                phoneno: {

                    required: true,

                    number: true,

                    minlength: 10,

                    maxlength: 10







                },

                address: "required"
            },

            messages: {

                username: " Please enter your name",

                useremail:

                        {

                            required: "Please enter your email",

                            email: "Please enter valid email address"



                        },

                password:

                        {

                            required: "Please Enter password"



                        },

                confirmpassword:

                        {

                            required: "Plese Enter Confirm Password",

                            equalTo: "Password and confirm password are not match"



                        },

                phoneno:

                        {

                            required: "Please Enter Phone Number",

                            minlength: "Please Enter only 10 digits",

                            maxlength: "Please Enter only 10 digits",

                            number: "Please Enter only number"







                        },

                address: " Please enter your address"
            },

            submitHandler: function (form)



            {



                $('#loader').show();

                var num =  $(".selected-dial-code").html();

                var data = new FormData(form);

                data.append("code", num);

                $.ajax({

                    url: "<?php echo SERVER_URL ?>services/registration",

                    type: 'POST',

                    dataType: "json",

                    data: data,

                    contentType: false,

                    processData: false,

                    success: function (data) {



                        $('#loader').hide();



                        if (data['status'] == '1') {







                            $('#sign-up').modal('hide');



                            bootbox.alert({

                                backdrop: 'static',

                                closeButton: false,

                                title: 'Thank you for joining!',

                                message: 'Please check your email to activate your account.',

                                callback: function () {



                                    window.location.href = '<?= SERVER_URL ?>';



                                }



                            });



                        } else if (data['status'] == '2') {



                            bootbox.alert({

                                backdrop: 'static',

                                closeButton: false,

                                title: 'Error',

                                message: 'Email Already Exist!',

                                callback: function () {







                                }



                            });



                        } else {



                            bootbox.alert({

                                backdrop: 'static',

                                closeButton: false,

                                title: 'Error',

                                message: 'Something Went Wrong !',

                                callback: function () {

                                    window.location.href = '<?= SERVER_URL ?>';

                                }

                            });



                        }



                    }



                });



            }



        });

    });



</script>   



<!--datepicker-->



<script src="<?= SERVER_URL ?>js/bootstrap-datepicker.js"></script>



<script src="<?= SERVER_URL ?>js/datepicker-js.js"></script>