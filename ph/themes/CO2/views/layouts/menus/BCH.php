<!-- Navigation -->
<nav id="mainNav" class="navbar navbar-default navbar-fixed-top navbar-custom">
    <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header pull-left">
           
            <a href="#" class="btn btn-link menu-btn-back-category pull-left no-padding lbh" 
                <?php //if( $subdomain != "welcome" ) { echo 'data-target="#modalMainMenu" data-toggle="modal"' } ?>
            >
                <img src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/img/LOGOS/<?php echo Yii::app()->params["CO2DomainName"]; ?>/logo-min.png" 
                     class="logo-menutop pull-left" height=30>
            </a>
            <span class="hidden-xs skills font-montserrat"><?php echo $mainTitle; ?></span>
            <?php 
                $params = CO2::getThemeParams(); 
            ?>
            
        </div>


        <button class="btn-show-map"  data-toggle="tooltip" data-placement="bottom" 
                title="<?php Yii::t("common", "Show the map") ?>">
            <i class="fa fa-map"></i>
        </button>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="pull-right navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav navbar-right">
                <li class="pull-right">
                    <button class="letter-orange font-montserrat" id="btn-open-search-bar">
                        <span><i class="fa fa-2x fa-search"></i></span>
                    </button>
                    <?php if (!@Yii::app()->session["userId"]){ ?>
                        <button class="letter-orange font-montserrat btn-menu-connect" 
                            data-toggle="modal" data-target="#modalLogin">
                            <span><i class="fa fa-2x fa-user-circle"></i></span>
                        </button>
                    <?php }else{ 
                        $profilThumbImageUrl = Element::getImgProfil($me, "profilThumbImageUrl", $this->module->assetsUrl); ?>
                        <button class="letter-orange font-montserrat btn-show-mainmenu"> 
                                <img class="img-circle" id="menu-thumb-profil" style="margin-top:-10px;" 
                                     width="25" height="25" src="<?php echo $profilThumbImageUrl; ?>" alt="image" >
                        </button>
                        <div class="dropdown pull-right" id="dropdown-user">
                            <div class="dropdown-main-menu">
                                <ul class="dropdown-menu arrow_box">
                                    <li class="text-left">
                                        <a href="<?php echo Yii::app()->createUrl('/co2/person/logout'); ?>" 
                                            class="bg-white letter-red logout">
                                            <i class="fa fa-sign-out"></i> <?php echo Yii::t("common", "Log Out") ; ?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    <?php } ?>
                    <button class="letter-orange font-montserrat">
                        <span><i class="fa fa-2x fa-flag"></i> FR</span>
                    </button>
                </li>
            </ul>
        </div>
        
            
        <div class="pull-right hidden-xs col-sm-5 col-md-5 col-lg-5 hidden" id="input-search">
            <button class="btn btn-default hidden-xs pull-right menu-btn-start-search btn-directory-type" 
                    data-type="<?php echo @$type; ?>">
                    <i class="fa fa-search"></i>
            </button>
            <div id="input-sec-search" class="pull-right col-sm-10 col-md-10 col-lg-10 no-padding">
                <input type="text" class="form-control" id="second-search-bar" 
                       placeholder="<?php echo $placeholderMainSearch; ?>">
                <div class="dropdown-result-global-search hidden-xs col-sm-6 col-md-5 col-lg-5 no-padding"></div>
            </div>
            
        </div>
            
    </div>
    <!-- /.container-fluid -->

</nav>
<?php $this->renderPartial($layoutPath.'modals.'.Yii::app()->params["CO2DomainName"].'.loginRegister', 
                            array("subdomain" => $subdomain)); ?>

<?php if(isset(Yii::app()->session['userId'])) 
        $this->renderPartial($layoutPath.'notifications'); ?>

<?php $this->renderPartial($layoutPath.'formCreateElement'); ?>


<script>
jQuery(document).ready(function() {    
    $("#input-search").hide();
    $("#btn-open-search-bar").click(function(){ 
        if($("#input-search").hasClass("hidden")){
            $("#input-search").removeClass("hidden");
            $("#input-search").show(500);
        }
        else {
            $("#input-search").hide(500);
            setTimeout(function(){
                $("#input-search").addClass("hidden");
            }, 600);
        }
    });
});
</script> 