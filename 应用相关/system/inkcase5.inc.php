<?php
mb_internal_encoding("UTF-8");


define("DEBUG",false);
define("INKCASE_I5_DEV",true);
define("POWER_STATE","/sys/android_power/state");
define("SCREEN_W", 360);
define("SCREEN_H", 600);
define("DEFAULT_FONTFILE","/opt/qte/fonts/msyh.ttf");


if( DEBUG ){
  #如果是在本地调试可以在浏览器中输出，执行一下这里就够了
  function imagefile(resource $im , string $filename , int  $fbmode ){
    imagejpeg($im);
    return ;
  }
}


###############################################################
#在指定的位置画图
###############################################################
function draw_bitmap($ar,$x,$y){
  $fp =fopen("/dev/fb","wb+");  
  $lines = count($ar);          
  for($i = 0 ; $i < $lines ; $i++){
    fseek( $fp , (($i+$y)*360+$x)*2 , 0 );
    $str = build_line($ar[$i]);           
    fwrite($fp,$str);                     
  }                                       
  fclose($fp);                            
}                                         
###############################################################
#生成一条线上的像素集二进制串
###############################################################
function build_line($str){                
  $i = strlen($str);                      
  $bin = "";                              
  for($j=0;$j<$i;$j++){                   
    $ch = substr($str,$j,1);              
    if( $ch == 0 ){                       
        $bin .= pack("S",0x0000);         
    }else                                 
    if( $ch == 1 ){                       
        $bin .= pack("S",0xFFFF);
    }else{                       
        $bin .= pack("S",0xEEEE);
    }                                     
  }                              
  return $bin;                   
}            
###############################################################
#把外部设备叫床
###############################################################
function dev_wakeup(){
  file_put_contents(POWER_STATE,'wakeup');
}
###############################################################
#全体设备睡觉
###############################################################
function dev_sleep(){
  file_put_contents(POWER_STATE,'standby');
}
###############################################################
#菜单驱动
###############################################################
/*
  检测是否需要显示菜单
*/
function menu_status(string $app){
   $flag = sprintf("/tmp/%s.menu.show",$app);
   return file_exists($flag);
}
function menu_select(string $app,array $items,string $key){
   $flag = sprintf("/tmp/%s.menu.show",$app);
   file_put_contents($flag,"1");
   _menu_create($app,$items);
   $fn = sprintf("/tmp/%s.menu.current",$app);
   $sel = unserialize(file_get_contents($fn));
   switch($key){
   	case 'n':
   		$sel['sel']++;
   		file_put_contents($fn,$d=serialize($sel));
   		echo $d;
   		break;
   	case 'd':
   		$fd = sprintf("/tmp/%s.menu.json",$app);
   		$items = unserialize(file_get_contents($fd));
   		unlink($flag);
   		unlink($fn);
   		$now = $sel['sel'] % $sel['row'];
   		printf("select = %d \n",$now);
   		return $items[$now];
   	case 'p':
   		unlink($flag);
   		unlink($fn);
   		return "";
   }
   _menu_driver($app); 
}

/*
  这是内部函数，用来生成和菜单有关的数据文件在内存中
*/
function _menu_create(string $app, array $items){
  $file = sprintf("/tmp/%s.menu.json",$app);
  $str = serialize($items);
  $old = @file_get_contents($file);
  if( $str == $old ){
    return true;
  }
  file_put_contents($file,serialize($items));
  #计算纵横
  $num = sizeof($items);
  $ar = [];
  foreach($items as $item){
    $ar [] =  mb_strlen($item);
  }
  #最大长度
  $max = max($ar);
  print_r($ar);
  $sel ['col'] = $max ; 
  $sel ['row'] = count($items);
  $sel ['sel'] = 0 ;
  
  
  $file = sprintf("/tmp/%s.menu.current",$app);
  file_put_contents($file,serialize($sel));
  return true;
}
/*
  内部函数，这是核心菜单显示驱动
*/
function _menu_driver(string $app){
  $FONT_HEIGHT = 35;
  $FONT_WIDTH = 20;
  $FONT_SIZE = 20;
  $im = imagecreatetruecolor(SCREEN_W,SCREEN_H);
  $white=imagecolorallocate($im,255,255,255);
  $black=imagecolorallocate($im,0,0,0);
  imagefilledrectangle($im,0,0,SCREEN_W,SCREEN_H,$white);
  /*
  imagerectangle($im,100,100+5,100+4*$FONT_WIDTH,100-$FONT_HEIGHT,$black);
  imagettftext($im,$FONT_SIZE,0,100,100,$black,
    DEFAULT_FONTFILE,"Tjst");
  imagefile($im,'/dev/fb',1);
  return true;
  */
  #检查是否需要显示
  $file = sprintf("/tmp/%s.menu.show",$app);
  if( ! file_exists($file) ){
    return;
  }
  #取得配置信息
  $file = sprintf("/tmp/%s.menu.json",$app);
  $items = unserialize(file_get_contents($file));
  $file = sprintf("/tmp/%s.menu.current",$app);
  $sel = unserialize(file_get_contents($file));
  $now = $sel['sel'] % $sel['row'];
  #画外框 
  $BX = 20 ; 
  $BY = 20 ;
  $BX1 = SCREEN_W-20 ; 
  $BY1 = $BY + $sel['col'] * $FONT_HEIGH ;
  #imagerectangle($im,$BX-1,$BY-1,$BX1 +1 ,$BY1 + 1 ,$black);
  #画内里
  for($i = 0 ; $i < $sel['row'] ; $i++ ){
    $ty = ($i+1)*$FONT_HEIGHT;
    $tx = $BX+1;
    $tx1 = $BX1-2;
    $ty1 = $ty - $FONT_HEIGHT + 2;
    
    if( $i == $now ){
      imagefilledrectangle($im,$tx,$ty,$tx1,$ty1,$black);
      imagettftext($im,$FONT_SIZE,0,$tx+4,$ty-5,$white,
        DEFAULT_FONTFILE,$items[$i]);
    }else{
      imagerectangle($im,$tx,$ty,$tx1,$ty1,$black);
      imagettftext($im,$FONT_SIZE,0,$tx+4,$ty-5,$black,
        DEFAULT_FONTFILE,$items[$i]);
    }
  }
  imagefile($im,"/dev/fb",1);
}





