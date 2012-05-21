<?php

$url=parse_url(getenv("CLEARDB_DATABASE_URL"));

    mysql_connect(
            $server = $url["host"],
            $username = $url["user"],
            $password = $url["pass"]);
            $db=substr($url["path"],1);

    mysql_select_db('heroku_19af8ba71100e6e');
		mysql_query("SET NAMES 'utf8'");  
		mysql_query("SET CHARACTER SET 'utf8'");  
		mysql_query("SET COLLATION_CONNECTION = 'utf8_general_ci'"); 

require_once('AppInfo.php');

if (substr(AppInfo::getUrl(), 0, 8) != 'https://' && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  header('Location: https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
  exit();
}

require_once('utils.php');


require_once('sdk/src/facebook.php');

$facebook = new Facebook(array(
  'appId'  => AppInfo::appID(),
  'secret' => AppInfo::appSecret(),
));

$user_id = $facebook->getUser();
$access_token = $facebook->getAccessToken();

if ($user_id) {
  try {
  
    $basic = $facebook->api('/me');
  } catch (FacebookApiException $e) {

    if (!$facebook->getUser()) {
      header('Location: '. AppInfo::getUrl($_SERVER['REQUEST_URI']));
      exit();
    }
  }

  $likes = idx($facebook->api('/me/likes?limit=4'), 'data', array());


  $friends = idx($facebook->api('/me/friends?token='.$access_token), 'data', array());


  $photos = idx($facebook->api('/me/photos?limit=16'), 'data', array());

  $app_using_friends = $facebook->api(array(
    'method' => 'fql.query',
    'query' => 'SELECT uid, name FROM user WHERE uid IN(SELECT uid2 FROM friend WHERE uid1 = me()) AND is_app_user = 1'
  ));
}

$app_info = $facebook->api('/'. AppInfo::appID());

$app_name = idx($app_info, 'name', '');

?>
<!DOCTYPE html>
<html xmlns:fb="http://ogp.me/ns/fb#" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=yes" />

    <title><?php echo he($app_name); ?></title>
    <link rel="stylesheet" href="stylesheets/screen.css" media="Screen" type="text/css" />
    <link rel="stylesheet" href="stylesheets/mobile.css" media="handheld, only screen and (max-width: 480px), only screen and (max-device-width: 480px)" type="text/css" />

   <meta property="og:title" content="<?php echo he($app_name); ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo AppInfo::getUrl(); ?>" />
    <meta property="og:image" content="<?php echo AppInfo::getUrl('/logo.png'); ?>" />
    <meta property="og:site_name" content="<?php echo he($app_name); ?>" />
    <meta property="og:description" content="My first app" />
    <meta property="fb:app_id" content="<?php echo AppInfo::appID(); ?>" />

    <script type="text/javascript" src="/javascript/jquery-1.7.1.min.js"></script>

    <script type="text/javascript">
      function logResponse(response) {
        if (console && console.log) {
          console.log('The response was', response);
        }
      }

      $(function(){

        $('#postToWall').click(function() {
          FB.ui(
            {
              method : 'feed',
              link   : $(this).attr('data-url')
            },
            function (response) {

              if (response != null) {
                logResponse(response);
              }
            }
          );
        });

        $('#sendToFriends').click(function() {
          FB.ui(
            {
              method : 'send',
              link   : $(this).attr('data-url')
            },
            function (response) {

              if (response != null) {
                logResponse(response);
              }
            }
          );
        });

        $('#sendRequest').click(function() {
          FB.ui(
            {
              method  : 'apprequests',
              message : $(this).attr('data-message')
            },
            function (response) {

              if (response != null) {
                logResponse(response);
              }
            }
          );
        });
      });
    </script>

  </head>
  <body>
    <div id="fb-root"></div>
    <script type="text/javascript">
      window.fbAsyncInit = function() {
        FB.init({
          appId      : '<?php echo AppInfo::appID(); ?>',
          channelUrl : '//<?php echo $_SERVER["HTTP_HOST"]; ?>/channel.html',
          status     : true,
          cookie     : true,
          xfbml      : true 
        });


        FB.Event.subscribe('auth.login', function(response) {

          window.location = window.location;
        });

        FB.Canvas.setAutoGrow();
      };


      (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/all.js";
        fjs.parentNode.insertBefore(js, fjs);
      }(document, 'script', 'facebook-jssdk'));
    </script>

    <header class="clearfix">
      <?php if (isset($basic)) { ?>
      <p id="picture" style="background-image: url(https://graph.facebook.com/<?php echo he($user_id); ?>/picture?type=normal)"></p>

      <div>
        <h1>Hoşgeldin, <strong><?php echo he(idx($basic, 'name')); ?></strong></h1>
        <p class="tagline">
          <a href="<?php echo he(idx($app_info, 'link'));?>" target="_top"><?php echo he($app_name); ?></a>
        </p>

        <div id="share-app">
          <p>Bu uygulama ile paylaş:</p>
          <ul>
            <li>
              <a href="#" class="facebook-button" id="postToWall" data-url="<?php echo AppInfo::getUrl(); ?>">
                <span class="plus">Duvarına gönder</span>
              </a>
            </li>
            <li>
              <a href="#" class="facebook-button speech-bubble" id="sendToFriends" data-url="<?php echo AppInfo::getUrl(); ?>">
                <span class="speech-bubble">Mesaj at</span>
              </a>
            </li>
            <li>
              <a href="#" class="facebook-button apprequests" id="sendRequest" data-message="Güzel uygulama denemelisin">
                <span class="apprequests">İstek gönder</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
      <?php } else { ?>
      <div>
        <h1>Hoşgeldin</h1>
        <div class="fb-login-button" data-scope="user_likes,user_photos"></div>
      </div>
      <?php } ?>
    </header>

    <?php
      if ($user_id) {
    ?>

    <section id="samples" class="clearfix">
      <h1>Arkadaşlığından Çıkaran Kişileri Bul</h1>
		<?php
			$status = false;
			$count = 0;
			$id = mysql_fetch_assoc(mysql_query("SELECT max(idfriendlist) from friendlist"));
			$id = $id['max(idfriendlist)'];
 			$iduser = he(idx($basic,'id'));
		  $result = mysql_query("SELECT friendid,iduser,friendname from friendlist where iduser = \"$iduser\"");

		  if ( mysql_num_rows($result) == 0 ) {
	  		foreach ($friends as $f){
		      $friendid = he(idx($f,'id'));
		      $friendname = he(idx($f,'name'));
					$id = $id + 1;
		      mysql_query("INSERT INTO friendlist(idfriendlist,friendid,iduser,friendname) VALUES(\"$id\",\"$friendid\",\"$iduser\",\"$friendname\")");
		    }
			?>
			<div class="successkutu">
				<div class="textsuccess">
					<?php
						echo "Arkadaş bilgileriniz kaydedildi.";
					?>
				</div>
			</div>
		<?php
			}
		  else{
				$fr = Array();
				foreach ($friends as $f){
					array_push($fr,he(idx($f,'id')));	
				}
				?><table style="margin:auto;"><?php
				// Silen kişiyi bulalım.
		    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					if (!in_array($row["friendid"],$fr)){
						$status = true;
						if ($count == 0){
						?>
     		     <div class="errorkutu">
        	    <div class="texterror">
          	   <?php
            	  echo "Senin sildiğin veya seni silmiş olan kişiler.";
								$count = $count + 1;
            	 ?>
         	  	 </div>
       		  </div>
						<?php
						 }	
						?>				
						<tr>
			      	<td>
      			  	<a href="https://www.facebook.com/<?php echo $row["friendid"]; ?>" target="_top">
            			<img style="padding-right: 7px;" src="https://graph.facebook.com/<?php echo $row["friendid"] ?>/picture?type=square" alt="<?php echo $row["friendname"]; ?>">
           		 	</a>
						  </td>	
							<td>
      			  	<a href="https://www.facebook.com/<?php echo $row["friendid"]; ?>" target="_top">
            			<?php echo $row["friendname"]; ?>
           		  </a>
							</td>
        	 </tr>
					<?php
					}
	  	  }
				?></table> <?php
           mysql_query("DELETE FROM friendlist where iduser = \"$iduser\"");
           foreach ($friends as $f){
            $friendid = he(idx($f,'id'));
            $friendname = he(idx($f,'name'));
						$id = $id + 1;
            mysql_query("INSERT INTO friendlist(idfriendlist,friendid,iduser,friendname) VALUES(\"$id\",\"$friendid\",\"$iduser\",\"$friendname\")");
					}

				if ( !$status ){
	      ?>
  		    <div class="successkutu">
      		  <div class="textsuccess">
         		 <?php
          	  echo "Silen bir kişi bulunamadı.";
        	   ?>
           </div>
	       </div>
       <?php

        }
				
 		 }
	 ?>
			<div style="padding-left:50px;">
      <div class="list inline">
        <h3>Son paylaşılan photoların</h3>
        <ul class="photos">
          <?php
            $i = 0;
            foreach ($photos as $photo) {

              $id = idx($photo, 'id');
              $picture = idx($photo, 'picture');
              $link = idx($photo, 'link');

              $class = ($i++ % 4 === 0) ? 'first-column' : '';
          ?>
          <li style="background-image: url(<?php echo he($picture); ?>);" class="<?php echo $class; ?>">
            <a href="<?php echo he($link); ?>" target="_top"></a>
          </li>
          <?php
            }
          ?>
        </ul>
      </div>


      <div class="list">
        <h3>Beğenilerin</h3>
        <ul class="things">
          <?php
            foreach ($likes as $like) {

              $id = idx($like, 'id');
              $item = idx($like, 'name');

          ?>
          <li>
            <a href="https://www.facebook.com/<?php echo he($id); ?>" target="_top">
              <img src="https://graph.facebook.com/<?php echo he($id) ?>/picture?type=square" alt="<?php echo he($item); ?>">
              <?php echo he($item); ?>
            </a>
          </li>
          <?php
            }
          ?>
        </ul>
      </div>

      <div class="listiki">
				<div class="iki">
	        <h3>Bu uygulamayı kullanan arkadaşların</h3>
				</div>
			</div>
			<div class="list">
        <ul class="friends">
          <?php
            foreach ($app_using_friends as $auf) {

              $id = idx($auf, 'uid');
              $name = idx($auf, 'name');
          ?>
          <li>
            <a href="https://www.facebook.com/<?php echo he($id); ?>" target="_top">
              <img src="https://graph.facebook.com/<?php echo he($id) ?>/picture?type=square" alt="<?php echo he($name); ?>">
              <?php echo he($name); ?>
            </a>
          </li>
          <?php
            }
          ?>
        </ul>
      </div>
     </div>
    </section>

    <?php
      }
    ?>
  </body>
</html>
