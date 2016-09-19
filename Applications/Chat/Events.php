<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

/**
 * 聊天主逻辑
 * 主要是处理 onMessage onClose 
 */
use \GatewayWorker\Lib\Gateway;

class Events
{
    static $init_users;//待使用的用户信息
    static $after_users;
    static $faces;//表情数组
    public static function onWorkerStart($businessWorker){
       self::init();
       self::init_faces();
    }

    public static function init(){
        if((empty(self::$init_users))or(!count(self::$init_users)>0)){
            $card_list = json_decode(file_get_contents("http://vgdb.ptbus.com/api/?s=card_list"),true)['result'];
            array_walk($card_list,function($value,$key){
                self::$init_users[] = ['photo'=>$value['icon'],'name'=>$value['name']];
            });
        }
//        var_dump(self::$init_users);
    }

    public static function init_faces(){
        if(empty(self::$faces)or(!count(self::$faces)>0)){
            $str = '{"\u5fae\u7b11":"http:\/\/www.imooc.com\/static\/img\/smiley\/1.png","\u4e0d":"http:\/\/www.imooc.com\/static\/img\/smiley\/2.png","\u4eb2\u4eb2":"http:\/\/www.imooc.com\/static\/img\/smiley\/3.png","\u65e0\u804a":"http:\/\/www.imooc.com\/static\/img\/smiley\/4.png","\u9f13\u638c":"http:\/\/www.imooc.com\/static\/img\/smiley\/5.png","\u4f24\u5fc3":"http:\/\/www.imooc.com\/static\/img\/smiley\/6.png","\u5bb3\u7f9e":"http:\/\/www.imooc.com\/static\/img\/smiley\/7.png","\u95ed\u5634":"http:\/\/www.imooc.com\/static\/img\/smiley\/8.png","\u800d\u9177":"http:\/\/www.imooc.com\/static\/img\/smiley\/9.png","\u65e0\u8bed":"http:\/\/www.imooc.com\/static\/img\/smiley\/10.png","\u53d1\u6012":"http:\/\/www.imooc.com\/static\/img\/smiley\/11.png","\u60ca\u8bb6":"http:\/\/www.imooc.com\/static\/img\/smiley\/12.png","\u59d4\u5c48":"http:\/\/www.imooc.com\/static\/img\/smiley\/13.png","\u9177":"http:\/\/www.imooc.com\/static\/img\/smiley\/14.png","\u6c57":"http:\/\/www.imooc.com\/static\/img\/smiley\/15.png","\u95ea":"http:\/\/www.imooc.com\/static\/img\/smiley\/16.png","\u653e\u5c41":"http:\/\/www.imooc.com\/static\/img\/smiley\/17.png","\u6d17\u6fa1":"http:\/\/www.imooc.com\/static\/img\/smiley\/18.png","\u5076\u8036":"http:\/\/www.imooc.com\/static\/img\/smiley\/19.png","\u56f0":"http:\/\/www.imooc.com\/static\/img\/smiley\/20.png","\u5492\u9a82":"http:\/\/www.imooc.com\/static\/img\/smiley\/21.png","\u7591\u95ee":"http:\/\/www.imooc.com\/static\/img\/smiley\/22.png","\u6655":"http:\/\/www.imooc.com\/static\/img\/smiley\/23.png","\u8870":"http:\/\/www.imooc.com\/static\/img\/smiley\/24.png","\u88c5\u9b3c":"http:\/\/www.imooc.com\/static\/img\/smiley\/25.png","\u53d7\u4f24":"http:\/\/www.imooc.com\/static\/img\/smiley\/26.png","\u518d\u89c1":"http:\/\/www.imooc.com\/static\/img\/smiley\/27.png","\u62a0\u9f3b":"http:\/\/www.imooc.com\/static\/img\/smiley\/28.png","\u5fc3\u5bd2":"http:\/\/www.imooc.com\/static\/img\/smiley\/29.png","\u6012":"http:\/\/www.imooc.com\/static\/img\/smiley\/30.png","\u51c4\u51c9":"http:\/\/www.imooc.com\/static\/img\/smiley\/31.png","\u6084\u6084":"http:\/\/www.imooc.com\/static\/img\/smiley\/32.png","\u594b\u6597":"http:\/\/www.imooc.com\/static\/img\/smiley\/33.png","\u54ed":"http:\/\/www.imooc.com\/static\/img\/smiley\/34.png","\u8d5e":"http:\/\/www.imooc.com\/static\/img\/smiley\/35.png","\u5f00\u5fc3":"http:\/\/www.imooc.com\/static\/img\/smiley\/36.png"}';
            self::$faces = json_decode($str,true);
        }
    }

    public static function login($client_id){
        $k = array_rand(self::$init_users,1);
        self::$after_users[$client_id] = self::$init_users[$k];
    }
   /**
    * 有消息时
    * @param int $client_id
    * @param mixed $message
    */
   public static function onMessage($client_id, $message)
   {
        // debug
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:".json_encode($_SESSION)." onMessage:".$message."\n";
        
        // 客户端传递的是json数据
        $message_data = json_decode($message, true);
        if(!$message_data)
        {
            return ;
        }
        // 根据类型执行不同的业务
        switch($message_data['type'])
        {
            // 客户端回应服务端的心跳
            case 'pong':
                return;
            // 客户端登录 message格式: {type:login, name:xx, room_id:1} ，添加到客户端，广播给所有客户端xx进入聊天室
            case 'login':
                self::login($client_id);
                // 判断是否有房间号
                if(!isset($message_data['room_id']))
                {
                    throw new \Exception("\$message_data['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }
                
                // 把房间号昵称放到session中
                $room_id = $message_data['room_id'];
//                $client_name = htmlspecialchars($message_data['client_name']);
                $client_name = self::$after_users[$client_id]['name'];
                $client_photo = self::$after_users[$client_id]['photo'];
                $_SESSION['room_id'] = $room_id;
                $_SESSION['client_name'] = $client_name;
                $_SESSION['client_photo'] = $client_photo;
                // 获取房间内所有用户列表
                $clients_list = Gateway::getClientSessionsByGroup($room_id);
                foreach($clients_list as $tmp_client_id=>$item)
                {
                    $clients_list[$tmp_client_id] = $item['client_name'];
                }
                $clients_list[$client_id] = $client_name;
                
                // 转播给当前房间的所有客户端，xx进入聊天室 message {type:login, client_id:xx, name:xx} 
                $new_message = array('client_photo'=>$client_photo,'type'=>$message_data['type'], 'client_id'=>$client_id, 'client_name'=>htmlspecialchars($client_name), 'time'=>date('Y-m-d H:i:s'));
                Gateway::sendToGroup($room_id, json_encode($new_message));
                Gateway::joinGroup($client_id, $room_id);
               
                // 给当前用户发送用户列表 
                $new_message['client_list'] = $clients_list;
                Gateway::sendToCurrentClient(json_encode($new_message));
                return;
                
            // 客户端发言 message: {type:say, to_client_id:xx, content:xx}
            case 'say':
                // 非法请求$client_id === Context::$client_id
                if(!isset($_SESSION['room_id']))
                {
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $room_id = $_SESSION['room_id'];
                $client_name = $_SESSION['client_name'];
                $client_photo = $_SESSION['client_photo'];

                // 私聊
                if($message_data['to_client_id'] != 'all')
                {
                    $new_message = array(
                        'type'=>'say',
                        'from_client_id'=>$client_id,
                        'from_client_name' =>$client_name,
                        'to_client_id'=>$message_data['to_client_id'],
                        'client_photo'=>$client_photo,
                        'content'=>"<b>对你说: </b>".nl2br(htmlspecialchars($message_data['content'])),
                        'time'=>date('Y-m-d H:i:s'),
                    );
                    Gateway::sendToClient($message_data['to_client_id'], json_encode($new_message));
                    $new_message['content'] = "<b>你对".htmlspecialchars($message_data['to_client_name'])."说: </b>".nl2br(htmlspecialchars($message_data['content']));
                    return Gateway::sendToCurrentClient(json_encode($new_message));
                }
                
                $new_message = array(
                    'type'=>'say',
                    'from_client_id'=>$client_id,
                    'from_client_name' =>$client_name,
                    'to_client_id'=>'all',
                    'client_photo'=>$client_photo,
                    'content'=>nl2br(htmlspecialchars($message_data['content'])),
                    'time'=>date('Y-m-d H:i:s'),
                );
                return Gateway::sendToGroup($room_id ,json_encode($new_message));
            case 'send_face':
                // 非法请求$client_id === Context::$client_id
                if(!isset($_SESSION['room_id']))
                {
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $room_id = $_SESSION['room_id'];
                $client_name = $_SESSION['client_name'];
                $client_photo = $_SESSION['client_photo'];

                if(($message_data['content'])){
                    $message_data['content'] = '<img src="'.self::$faces[$message_data['content']].'">';
                }
                // 私聊
                if($message_data['to_client_id'] != 'all')
                {
                    $new_message = array(
                        'type'=>'say',
                        'from_client_id'=>$client_id,
                        'from_client_name' =>$client_name,
                        'to_client_id'=>$message_data['to_client_id'],
                        'client_photo'=>$client_photo,
                        'content'=>"<b>对你说: </b>".nl2br(($message_data['content'])),
                        'time'=>date('Y-m-d H:i:s'),
                    );
                    Gateway::sendToClient($message_data['to_client_id'], json_encode($new_message));
                    $new_message['content'] = "<b>你对".($message_data['to_client_name'])."说: </b>".nl2br(($message_data['content']));
                    return Gateway::sendToCurrentClient(json_encode($new_message));
                }

                $new_message = array(
                    'type'=>'say',
                    'from_client_id'=>$client_id,
                    'from_client_name' =>$client_name,
                    'to_client_id'=>'all',
                    'client_photo'=>$client_photo,
                    'content'=>nl2br(($message_data['content'])),
                    'time'=>date('Y-m-d H:i:s'),
                );
                return Gateway::sendToGroup($room_id ,json_encode($new_message));
        }
   }
   
   /**
    * 当客户端断开连接时
    * @param integer $client_id 客户端id
    */
   public static function onClose($client_id)
   {
       // debug
       echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id onClose:''\n";
       $client_photo = self::$after_users[$client_id]['photo'];
       // 从房间的客户端列表中删除
       if(isset($_SESSION['room_id']))
       {
           $room_id = $_SESSION['room_id'];
           $new_message = array(                        'client_photo'=>$client_photo,
               'type'=>'logout', 'from_client_id'=>$client_id, 'from_client_name'=>$_SESSION['client_name'], 'time'=>date('Y-m-d H:i:s'));
           Gateway::sendToGroup($room_id, json_encode($new_message));
       }
   }
  
}
