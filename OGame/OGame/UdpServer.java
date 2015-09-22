import java.io.*;
import java.net.*;
 
// 
 
public class UdpServer {
    int port;    // 連接埠
    String message;
    int limit = -1;
    private DatagramSocket socket;
    private String sourceIP = "";
    private int sourcePort = 0;
 
    public UdpServer(int pPort) {
        port = pPort;                            // 設定連接埠。
    }

    public void setMessage(String msg){
        this.message = msg;
    }

    public String getMessage(){
        return this.message;
    }

    public String getSourceIP(){return this.sourceIP;}
    public int getSourcePort(){return this.sourcePort;}
 
    public void run() throws Exception {
        final int SIZE = 8192;                    //
        byte buffer[] = new byte[SIZE];            //

        socket = new DatagramSocket(port);         // 設定接收的 UDP Socket.
        socket.setReuseAddress(true);

        int count = 0;
        //for (int count = 0; ; count++) {
            DatagramPacket packet = new DatagramPacket(buffer, buffer.length);
            
            if(limit>0){
                socket.setSoTimeout( (this.limit*1000) );
            }

            socket.receive(packet);                                    // 接收封包。

            //get source IP and port
            sourceIP = packet.getAddress().toString().substring(1);
            sourcePort = packet.getPort();

            String msg = new String(buffer, 0, packet.getLength());    // 將接收訊息轉換為字串。
            System.out.println("Msg["+count+"] : "+msg);                    // 印出接收到的訊息。

            if( msg != ""){
                this.setMessage(msg);
                //break;
            }

            socket.close();    
            socket = null;                                        // 關閉 UDP Socket.
       // }
    }

    public void setTimeLimit(int lt){
        this.limit = lt;
    }

    public void close(){
        socket.close();
    }
}