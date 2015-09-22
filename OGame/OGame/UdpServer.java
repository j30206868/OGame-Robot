import java.io.*;
import java.net.*;
 
// 
 
public class UdpServer {
    int port;    // �s����
    String message;
    int limit = -1;
    private DatagramSocket socket;
    private String sourceIP = "";
    private int sourcePort = 0;
 
    public UdpServer(int pPort) {
        port = pPort;                            // �]�w�s����C
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

        socket = new DatagramSocket(port);         // �]�w������ UDP Socket.
        socket.setReuseAddress(true);

        int count = 0;
        //for (int count = 0; ; count++) {
            DatagramPacket packet = new DatagramPacket(buffer, buffer.length);
            
            if(limit>0){
                socket.setSoTimeout( (this.limit*1000) );
            }

            socket.receive(packet);                                    // �����ʥ]�C

            //get source IP and port
            sourceIP = packet.getAddress().toString().substring(1);
            sourcePort = packet.getPort();

            String msg = new String(buffer, 0, packet.getLength());    // �N�����T���ഫ���r��C
            System.out.println("Msg["+count+"] : "+msg);                    // �L�X�����쪺�T���C

            if( msg != ""){
                this.setMessage(msg);
                //break;
            }

            socket.close();    
            socket = null;                                        // ���� UDP Socket.
       // }
    }

    public void setTimeLimit(int lt){
        this.limit = lt;
    }

    public void close(){
        socket.close();
    }
}