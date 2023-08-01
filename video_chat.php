<?php
    $event=$_GET['event'];
    $user_id=uniqid();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        body {
            background: #0098ff;
            display: flex;
            height: 100vh;
            margin: 0;
            align-items: center;
            justify-content: center;
            padding: 0 50px;
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
        }
        video {
            max-width: calc(50% - 100px);
            margin: 0 50px;
            box-sizing: border-box;
            border-radius: 2px;
            padding: 0;
            background: white;
        }
        .copy {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 16px;
            color: white;
        }
    </style>
</head>
<body>
    <div class="copy">Send your URL to a friend to start a video call</div>
    <video id="localVideo" autoplay muted></video>
    <video id="remoteVideo" autoplay></video>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
    <script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
    <script>
        var APP_CHANNEL="APP_CHANNEL";
        var APP_EVENT="<?php echo $event; ?>";
        var APP_KEY= "APP_KEY";
        var APP_CLUSTER = "APP_CLUSTER ";
        var user_id="<?php echo $user_id; ?>";
    </script>    
    <script>
        var pusher = new Pusher(APP_KEY, {
            cluster: APP_CLUSTER,
        });
        var channel = pusher.subscribe(APP_CHANNEL);
        /// code to check if channel is subscribed successfully and send user info below ///
        channel.bind('pusher:subscription_succeeded', () => {
            send_message(`{"user_id":"${user_id}"}`); 
        });
        /// code to check if channel is subscribed successfully and send user info above ///
        channel.bind(APP_EVENT, (data) => {
            //console.log(data);
            message=JSON.parse(data);
            console.log(message);
            if(message.hasOwnProperty('user_id')){
                if(message.user_id!=user_id){
                    isOfferer=false;
                    send_message(`{"start_webrtc":"true"}`);
                }else{
                    isOfferer=true;
                }
            }
            if(message.hasOwnProperty('start_webrtc')){
                if(message.start_webrtc=='true'){
                    console.log(`webrtc started, isOfferer - ${isOfferer}`);
                    startWebRTC(isOfferer);
                }
            }
            if(message.hasOwnProperty('sender_id')){
                if(message.sender_id==user_id){
                    return;
                }
                if (message.sdp) {
                // This is called after receiving an offer or answer from another peer
                pc.setRemoteDescription(new RTCSessionDescription(message.sdp), () => {
                    // When receiving an offer lets answer it
                    if (pc.remoteDescription.type === 'offer') {
                    pc.createAnswer().then(localDescCreated).catch(onError);
                    }
                }, onError);
                } else if (message.candidate) {
                // Add the new ICE candidate to our connections remote description
                pc.addIceCandidate(
                    new RTCIceCandidate(message.candidate), onSuccess, onError
                );
                }
            }
        });
    </script>   
    <script>
        function send_message(message){
            var dataa={"message":message,'event':APP_EVENT};
            $.ajax({
                url: "pusher.php",
                type: "POST",
                data: dataa,
                success: function(data) {
                    //console.log(data);
                    
                },
                error: function() {
                    alert('Some Error Occured.');
                }
            });
        }

    </script> 

    <script>
        const configuration = {
            iceServers: [{
                urls: 'stun:stun.l.google.com:19302'
            }]
        };

        let pc;

        function onSuccess() {};
        function onError(error) {
          console.log(error);
        };

        function startWebRTC(isOfferer) {
            pc = new RTCPeerConnection(configuration);
            
            // 'onicecandidate' notifies us whenever an ICE agent needs to deliver a
            // message to the other peer through the signaling server
            pc.onicecandidate = event => {
                if (event.candidate) {
                send_message(JSON.stringify({'sender_id':user_id,'candidate': event.candidate}));
                }
            };
            
            // If user is offerer let the 'negotiationneeded' event create the offer
            if (isOfferer) {
                pc.onnegotiationneeded = () => {
                pc.createOffer().then(localDescCreated).catch(onError);
                }
            }
            
            // When a remote stream arrives display it in the #remoteVideo element
            pc.onaddstream = event => {
                /*if(isOfferer==false){
                    alert("you got a call");
                }*/
                remoteVideo.srcObject = event.stream;
            };
            
            navigator.mediaDevices.getUserMedia({
                audio: true,
                video: true,
            }).then(stream => {
                // Display your local video in #localVideo element
                localVideo.srcObject = stream;
                // Add your stream to be sent to the conneting peer
                pc.addStream(stream);
            }, onError);
            }
            function localDescCreated(desc) {
            pc.setLocalDescription(
                desc,
                () => send_message(JSON.stringify({'sender_id':user_id,'sdp': pc.localDescription})),
                onError
            );
        }
    </script>    
</body>
</html>
