<style>
    @media only screen and (max-width: 992px) {
       img.visible-xs.frontarw {display: none !important}  
    }
</style>
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

<div class="banner">

    <div id="myCarousel" class="carousel slide" data-ride="carousel">

        <!-- Indicators -->

        <ol class="carousel-indicators">

            <?php
            $activeindicators = "active";

            $dataslide = "0";

            foreach ($sqlgetbanner as $getbanner) {
                ?>

                <li data-target="#myCarousel" data-slide-to="<?= $dataslide; ?>" class="<?= $activeindicators; ?>"></li>



                <?php
                $dataslide++;

                $activeindicators = "";
            }
            ?>

        </ol>



        <!-- Wrapper for slides -->

        <div class="carousel-inner" role="listbox">

            <?php
            $activebannerslider = "active";

            foreach ($sqlgetbanner as $getbanner) {
                ?>

                <div class="item caroban <?= $activebannerslider; ?>">
                    <?php if($getbanner['VideoId'] != 0){ ?>
                        <a href="<?= SERVER_URL ?>video/<?= $getbanner['VideoId']; ?>" alt="<?= $getbanner['ImageName'] ?>">
                    <?php } ?>
                        <img src="<?= ADMINURL . $getbanner['ImagePath'] ?>" class="bannerimg" alt="<?= $getbanner['ImageName'] ?>">

                        <div class="carousel-caption">

                            <img src="img/video-icon.png" alt="video-icon">

                            <h3><?= $getbanner['BannerName'] ?>

                                <p><?= $getbanner['BannerSlogan'] ?></p></h3>
                        </div>
                    <?php if($getbanner['VideoId'] != 0){ ?>
                    </a> 
                    <?php } ?>
                </div>

                <?php
                $activebannerslider = "";
            }
            ?>

        </div>



        <!-- Left and right controls -->

        <a class="left carousel-control" href="#myCarousel" role="button" data-slide="prev">

            <span class="" aria-hidden="true">

                <img src="img/left-arrow.png" alt="">

            </span>

            <span class="sr-only">Previous</span>

        </a>

        <a class="right carousel-control" href="#myCarousel" role="button" data-slide="next">

            <span class="" aria-hidden="true">

                <img src="img/right-arrow.png" alt="">

            </span>

            <span class="sr-only">Next</span>

        </a>

    </div>

</div>



<div id="all">

         

    <div id="content" class="container mt20 similar home1 adv_saf">
            
        <div class="row">
            <div class="col-sm-9 respindex vd_100">
                <div class="pd60">
                    <div class="allcategory">

                        <?php
//                print_r($sqlgetcategory);

                        $mt40 = "";

                        foreach ($sqlgetcategory as $sqlcategory) {
                            ?>

                            <div class="categorycount <?= $mt40; ?> ">

                                <div class="sec-hd"> 

                                    <a href="<?= SERVER_URL . "sub-category/" . $sqlcategory['CategoryId'] ?>"><h2 class="pdl15 wow fadeIn animated"><?= $sqlcategory['CategoryName']; ?></h2></a>

                                    <a href="<?= SERVER_URL . "sub-category/" . $sqlcategory['CategoryId'] ?>" class="pull-right vall">View All <i class="fa fa-angle-right"></i></a>

                                </div>





                                <div class="wow fadeInLeft animated">



                                    <div class="owl-carousel owl-theme">

                                        <?php
                                        $getcategoryvideourl = SERVER_URL . "services/categoryvideo?category=" . $sqlcategory['CategoryId'];

                                        $sqlgetcategoryvideo = $objfrntfunc->getcurldata($getcategoryvideourl);

                                        foreach ($sqlgetcategoryvideo as $getcategoryvideo) {
                                            ?>

                                            <div class="item">

                                                <a href="<?= SERVER_URL . "video/" . $getcategoryvideo['VideoId'] ?>">

                                                    <div class="fff trending-section">

                                                        <div class="thumbnail timing">

                                                            <img src="<?= ADMINURL . $getcategoryvideo['ImagePathThumb']; ?>"  alt="">

                                                                <i><?= $getcategoryvideo['VideoDuration'];?> </i>

                                                        </div>

                                                        <div class="caption">

                                                            <p>

                                                                <i class="text-uppercase"><?= $getcategoryvideo['Videotitle']; ?></i>

                                                                <b><?php
                                                                    if (!empty($getcategoryvideo['TagId'])) {
                                                                        //Start get video api	

                                                                        $gettagourl = SERVER_URL . "services/tag?tagid=" . $getcategoryvideo['TagId'];
                                                                        $sqlgettag = $objfrntfunc->getcurldata($gettagourl);
                                                                        $counttag = count($sqlgettag);
                                                                        //End get video api
                                                                        $i = 1;
                                                                        foreach ($sqlgettag as $sqltag) {
                                                                            echo $sqltag['TagName'];
                                                                            if ($counttag > 1 && $counttag != $i) {
                                                                                echo",";
                                                                            }
                                                                            $i++;
                                                                        }
                                                                    }
                                                                    ?></b>

                                                                <span><?php
                                                                    if (!empty($getcategoryvideo['actualview'])) {

                                                                        echo $getcategoryvideo['actualview'] . " Views";
                                                                    } else {

                                                                        echo " 0 Views";
                                                                    }
                                                                    ?>    <?= $getcategoryvideo['uploadday'] ?>  day ago 
                                                                </span>
                                                            </p>

                                                        </div>

                                                    </div>

                                                </a>

                                            </div>

                                            <?php
                                        }
                                        ?>

                                    </div>

                                </div>

                            </div>    

                            <?php
                            $mt40 = "mt40";
                        }
                        ?>

                    </div>

                    <?php if ($categorycount >= 4) { ?>                

                        <div class="hover-arw wow fadeInUp animated mt40"><a id="loadmorevideo" class="more">Show More <i class="fa fa-plus-square"></i> </a></div>

                    <?php } ?>

                </div>
            </div>

            <div class="col-sm-3 wow fadeInRight animated pd0 mt-40 mt_trd">
                                <a href="<?=SERVER_URL.'contact-us/'?>"><img src="<?= SERVER_URL ?>img/add.jpg" alt="couponcode" class="img-responsive"></a>

                <div class="col-sm-12 pd0 mt40"><h2>Trending Now</h2></div>

                <div class="trending-main col-sm-12 pd0 indextrensing">

                    <div class="trendingextra  row home-trending">

                        <?php
                        foreach ($sqlgettrendingvideo as $trendingvideo) {
                            ?>

                            <div class="trending-section mb20">
                                <a href="<?= SERVER_URL . "video/" . $trendingvideo['VideoId'] ?>">

                                    <div class="col-sm-5 indextrensing-lft">

                                        <div class="timing">

                                            <img src="<?= ADMINURL . $trendingvideo['ImagePathThumb'] ?>" alt="" class="img-responsive">

                                                  <i><?= $trendingvideo['VideoDuration'];?> </i>

                                        </div>



                                    </div>



                                    <div class="col-sm-7 pdl15 indextrensing-rgt">

                                        <p>

                                            <i><?= $trendingvideo['Videotitle'] ?></i>

                                            <b><?= $trendingvideo['CategoryName'] ?></b>

                                            <span><?php
                                                if (!empty($trendingvideo['actualview'])) {

                                                    echo $trendingvideo['actualview'] . " Views";
                                                } else {

                                                    echo " 0 Views";
                                                }
                                                ?>   </span>

                                        </p>

                                    </div>



                                </a>



                            </div>

                        <?php }
                        ?>

                    </div>

                    <a class="show" id="trendingloadmore"> Show More<i class="fa fa-angle-down"></i></a>

                </div>                     


            </div>

        </div>

    </div>


    <?php
    require_once './frontcommon/footer.php';
    ?>   



    <script type="text/javascript">

        $(document).on('click', '#loadmorevideo', function () {

            $('#loader').show();

            var categorycount = $('.categorycount').length;

            var jsonData = {"offset": categorycount, "getcategorybyloadmore": '1'};

            $.ajax({
                url: "<?php echo SERVER_URL ?>frontendattributes.php",
                type: 'POST',
                data: jsonData,
                success: function (data) {

                    $('#loader').hide();

                    if (!$.trim(data)) {

                        $('#loadmorevideo').hide();

                    }

                    $('.allcategory').append(data);

                    var owl = $(".owlloadmore");

                    owl.owlCarousel({
                        loop: true,
                        margin: 10,
                        responsiveClass: true,
                        responsive: {
                            0: {
                                items: 1,
                                nav: true

                            },
                            600: {
                                items: 3,
                                nav: false

                            },
                            1000: {
                                items: 4,
                                nav: true,
                                loop: false,
                                margin: 20

                            }

                        }

                    });

                }

            });

        });

        $(document).on('click', '#trendingloadmore', function () {

            $('#loader').show();

            var jsonData = {"gettrendingvideobyuser": '1', "trendingvideosloadmore": "1"};

            $.ajax({
                url: "<?php echo SERVER_URL ?>frontendattributes.php",
                type: 'POST',
                data: jsonData,
                success: function (data) {

                    $('#loader').hide();



                    $('.trendingextra').html(data);

                    $('#trendingloadmore').removeClass("show");

                    $('#trendingloadmore').hide();

//                    $('.trendingextra').addClass('fixtrainngvideo');

                }

            });

        });





    </script> 