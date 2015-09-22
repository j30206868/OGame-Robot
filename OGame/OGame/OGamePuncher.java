import java.io.*;
import java.net.*;

public class OGamePuncher extends Thread {
    int port;            // port : ³s±µ°ð
    InetAddress server; // InetAddress ¬O IP, ¦¹³Bªº server «üªº¬O¦øªA¾¹ IP
    String msg;           
   
    public static void main(String args[]) throws Exception {
        OGamePuncher ogp = new OGamePuncher("39.14.137.224", 8080, "msg");
        ogp.start();
    }
 
    public OGamePuncher(String pServer, int pPort, String pMsg) throws Exception {
        port = pPort;                             
        server = InetAddress.getByName(pServer); 
        msg = pMsg;                               
    }
 
    public void run() {
      
        while(1==1){
            try {
                byte buffer[] = msg.getBytes();                
  
                DatagramPacket packet = new DatagramPacket(buffer, buffer.length, server, port); 
                DatagramSocket socket = new DatagramSocket();    
                socket.send(packet);                           
                socket.close();                 

                Thread.sleep(2000);            
            } catch (Exception e) { e.printStackTrace(); }    

        }
    }
}