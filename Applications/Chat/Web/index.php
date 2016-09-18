<html><head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>简易的聊天室</title>
  <script type="text/javascript">
  //WebSocket = null;
  </script>
  <link href="/css/bootstrap.min.css" rel="stylesheet">
  <link href="/css/style.css" rel="stylesheet">
  <!-- Include these three JS files: -->
  <script type="text/javascript" src="/js/swfobject.js"></script>
  <script type="text/javascript" src="/js/web_socket.js"></script>
  <script type="text/javascript" src="/js/jquery.min.js"></script>

    <script type="text/javascript">
    WEB_SOCKET_SWF_LOCATION = "/swf/WebSocketMain.swf";
    WEB_SOCKET_DEBUG = true;
    var ws, name, client_list={};
    var current_id;


//    var c=document.getElementById("myCanvas");
//    var cxt=c.getContext("2d");
//    var img=new Image()
//    img.src="flower.png"
//    cxt.drawImage(img,0,0);

    // 连接服务端
    function connect() {
       // 创建websocket
       ws = new WebSocket("ws://"+document.domain+":7272");
       // 当socket连接打开时，输入用户名
       ws.onopen = onopen;
       // 当有消息时根据消息类型显示不同信息
       ws.onmessage = onmessage; 
       ws.onclose = function() {
    	  console.log("连接关闭，定时重连");
          connect();
       };
       ws.onerror = function() {
     	  console.log("出现错误");
       };
    }

    // 连接建立时发送登录信息
    function onopen()
    {
        // 登录
        var login_data = '{"type":"login","room_id":"<?php echo isset($_GET['room_id']) ? $_GET['room_id'] : 1?>"}';
        console.log("websocket握手成功，发送登录数据:"+login_data);
        ws.send(login_data);
    }

    // 服务端发来消息时
    function onmessage(e)
    {
        console.log(e.data);
        var data = eval("("+e.data+")");
        switch(data['type']){
            // 服务端ping客户端
            case 'ping':
                ws.send('{"type":"pong"}');
                break;
            // 登录 更新用户列表
            case 'login':

                //{"type":"login","client_id":xxx,"client_name":"xxx","client_list":"[...]","time":"xxx"}
                if(data['client_list'])
                {
                    client_list = data['client_list'];
                    current_id = data['client_id'];
                }
                else
                {
                    client_list[data['client_id']] = data['client_name']; 
                }
                say(data['client_id'], data['client_name'],  data['client_name']+' 加入了聊天室', data['time'],data['client_photo'],data);
                flush_client_list();
                console.log(data['client_name']+"登录成功");
                break;
            // 发言
            case 'say':
                //{"type":"say","from_client_id":xxx,"to_client_id":"all/client_id","content":"xxx","time":"xxx"}
                say(data['from_client_id'], data['from_client_name'], data['content'], data['time'],data['client_photo'],data);
                break;
            // 用户退出 更新用户列表
            case 'logout':
                //{"type":"logout","client_id":xxx,"time":"xxx"}
                say(data['from_client_id'], data['from_client_name'], data['from_client_name']+' 退出了', data['time'],data['client_photo']);
                delete client_list[data['from_client_id']];
                flush_client_list();
        }
    }

    // 提交对话
    function onSubmit() {
      var input = document.getElementById("textarea");
      var to_client_id = $("#client_list option:selected").attr("value");
      var to_client_name = $("#client_list option:selected").text();
      ws.send('{"type":"say","to_client_id":"'+to_client_id+'","to_client_name":"'+to_client_name+'","content":"'+input.value.replace(/"/g, '\\"').replace(/\n/g,'\\n').replace(/\r/g, '\\r')+'"}');
      input.value = "";
      input.focus();
    }

    // 刷新用户列表框
    function flush_client_list(){
    	var userlist_window = $("#userlist");
    	var client_list_slelect = $("#client_list");
    	userlist_window.empty();
    	client_list_slelect.empty();
    	userlist_window.append('<h4>在线用户</h4><ul>');
    	client_list_slelect.append('<option value="all" id="cli_all">所有人</option>');
    	for(var p in client_list){
            userlist_window.append('<li id="'+p+'">'+client_list[p]+'</li>');
            client_list_slelect.append('<option value="'+p+'">'+client_list[p]+'</option>');
        }
    	$("#client_list").val(select_client_id);
    	userlist_window.append('</ul>');
    }

    // 发言
    function say(from_client_id, from_client_name, content, time,photo,data){
        console.log(from_client_id);
        console.log(current_id);
        if(current_id ==  from_client_id){
            $("#dialog").append('<div class="speech_item">' +
                '<div style="float: right"><img style="width: 38px;float: right;" src="'+photo+'" class="user_icon" /><em style="text-align: right;" class="text-overflow">'
                +from_client_name+'<br>'+time+'</em></div><div style="clear:both;">' +
                '</div><p class="triangle-isosceles right">'+content+'</p> </div>');
        }else{
            $("#dialog").append('<div class="speech_item">' +
                '<img style="width: 38px" src="'+photo+'" class="user_icon" /> <em>'
                +from_client_name+' <br> '+time+'</em><div style="clear:both;">' +
                '</div><p class="triangle-isosceles top">'+content+'</p> </div>');
        }
//        $("#dialog").scrollTo(0,$("#dialog").scrollMaxY);
//        $("#dialog").scrollHeight(0);
        $("#dialog").scrollTop($("#dialog")[0].scrollHeight)

    }


    $(function(){
    	select_client_id = 'all';
	    $("#client_list").change(function(){
	         select_client_id = $("#client_list option:selected").attr("value");
	    });
    });
  </script>
    <script type="text/javascript">
        document.onkeydown=function(event){
            var e = event || window.event || arguments.callee.caller.arguments[0];
            if(e && e.keyCode==13){ // enter 键
                //要做的事情
                $(":submit").trigger('click');
            }
        };
    </script>
</head>
<body onload="connect();">
    <div class="container">
	    <div class="row clearfix">
            <div class="col-sm-4 column">
                <div class="thumbnail">
                    <div class="caption" id="userlist"></div>
                </div>
            </div>
	        <div class="col-sm-8 column">
	           <div class="thumbnail">
	               <div class="caption" id="dialog"></div>
	           </div>
	           <form onsubmit="onSubmit(); return false;">
	                <select style="margin-bottom:8px" id="client_list">
                        <option value="all">所有人</option>
                    </select>
                    <textarea class="textarea thumbnail" id="textarea"></textarea>
                    <div class="say-btn"><input type="submit" class="btn btn-default" value="发表" /></div>
               </form>
               <div>
               &nbsp;&nbsp;&nbsp;&nbsp;<b>房间列表:</b>（当前在&nbsp;房间<?php echo isset($_GET['room_id'])&&intval($_GET['room_id'])>0 ? intval($_GET['room_id']):1; ?>）<br>
               &nbsp;&nbsp;&nbsp;&nbsp;<a href="/?room_id=1">房间1</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="/?room_id=2">房间2</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="/?room_id=3">房间3</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="/?room_id=4">房间4</a>
               <br><br>
               </div>
	        </div>

	    </div>
    </div>
</body>
</html>
