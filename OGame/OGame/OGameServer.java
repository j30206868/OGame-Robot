import java.io.*;
import java.net.*;
import java.lang.management.ManagementFactory;

public class OGameServer extends Thread {
    int listenPort;  
    String message;
    public int pid;
    public int state;
    public String sourceIP = "";
    public int sourcePort = 0;
    public Process sProcess;
    public BufferedReader sProcessReader;

    //used by main
    private static final int ServerPort = 8080;
 
    public static void main(String args[]) throws Exception {

        //init server and listener
        System.out.println("Waiting for client getting connection...");
        OGameServer server = new OGameServer( OGameServer.ServerPort );
        OGameServerListener serverListener = new OGameServerListener( OGameServer.ServerPort, server );

        //listener starts to listen packages for server
        serverListener.start();
        server.start();                       
    }
 
    public OGameServer(int pPort) {
        listenPort = pPort;                        
        this.state = 0;
        this.pid = 0;
    }

    public void setMessage(String msg){
        this.message = msg;
    }

    public void setSourceIP(String sIP){
        this.sourceIP = sIP;
        System.out.println("Set This.ip: "+this.sourceIP);
    }

    public void setSourcePort(int sPort){
        this.sourcePort = sPort;
        System.out.println("Set This.port: "+this.sourcePort);
    }

    public String getSourceIP(){
        System.out.println("Get This.ip: "+this.sourceIP);
        return this.sourceIP;
    }
    public int getSourcePort(){
        System.out.println("Get This.port: "+this.sourcePort);
        return this.sourcePort;
    }

    public String getMessage(){
        return this.message;
    }
 
    public void writeLog(String str, boolean append){
        try{
            PrintWriter logStartW = new PrintWriter( new FileWriter("C:/Program Files (x86)/Web/www/OGame/OGame/log.txt", append));
            //PrintWriter logStartW = new PrintWriter( new FileWriter("C:/Program Files (x86)/Web/www/OGame/OGame/logJ30206868.txt", append));
            logStartW.println( str );
            logStartW.close();
        }catch(Exception e){
            System.out.println("Write Log Exception: "+e.getMessage());
        }
    }

    public void run() {
               
         //init variable
        sourceIP = "";
        sourcePort = 0;
        while(1==1){

            if( this.state == 0 ){
                //do nothing
                //System.out.println("State: idle...");
            }else if( this.state == 1){


                //inform client that server got messages.
                //System.out.println("Sending message to client...");
                //System.out.println("Client: "+sourceIP+":"+sourcePort);
                // reply message to client
                /*for (int i=0; i<5; i++) {

                    try{
                        UdpClient client = new UdpClient(sourceIP, sourcePort, "Working!");
                        client.run();

                        Thread.sleep(1000); 
                    }catch(InterruptedException e){
                        System.out.println("Server thread sleep(100) failed: "+e.getMessage());
                    }catch(Exception e){
                        System.out.println("Server reply client msg error: "+e.getMessage()); 
                    }
                    
                }*/

                // run batch
                System.out.println("Start run GuardEternal.bat...");
                //System.out.println("Start run GuardJ30206868.bat...");
                try{
                    Runtime rt = Runtime.getRuntime();
                    this.sProcess = rt.exec("GuardEternal.bat");
                    //this.sProcess = rt.exec("GuardJ30206868.bat");

                    this.pid = getLastPhpExe();

                    if(  this.pid == 0 ){
                        System.out.println("Get bat pid failed.\n");
                    }

                    Process pr = this.sProcess;
                    System.out.println("P: php.exe Pid:" + this.pid);
                    BufferedReader input = new BufferedReader(new InputStreamReader(pr.getInputStream()));

                    this.sProcessReader = input;

                    String line=null;

                    PrintWriter writer = new PrintWriter("RealState.txt", "UTF-8");
                    writer.println( 1 );
                    writer.close();

                    this.writeLog( "========================= Bat Start =========================",true );

                    while((line=input.readLine()) != null) {
                        this.writeLog( line,true );
                        System.out.println(line);
                    }

                    int exitVal = pr.waitFor();

                    this.writeLog( "========================= Bat end =========================",true );
                    System.out.println("Terminated.");
                }catch(Exception e){
                    this.writeLog("Execute bat failed: "+e.getMessage(), true);
                }
                //
            }

            try{
                Thread.sleep(1000); 
            }catch(InterruptedException e){
                System.out.println("Server thread sleep(1000) failed: "+e.getMessage());
            }
           
        }   
        
    }

    public int getLastPhpExe(){
        Runtime rt = Runtime.getRuntime();
        int lastPhpPid = 0;
        try{
            Process pr = rt.exec("cmd /C tasklist");
            BufferedReader input = new BufferedReader(new InputStreamReader(pr.getInputStream()));
            String line=null;
            System.out.println("Read input");
            
            while((line=input.readLine()) != null) {
                //this.writeLog( line,true );
                
                //System.out.println(line);
                String[] info = line.split(" +");
                //System.out.println(info.length);
                
                if( info.length >= 3){
                    //System.out.println(info[0]+info[1]);
                    if( info[0].equals("php.exe") ){
                        lastPhpPid = Integer.parseInt(info[1]);
                    }
                }
                //System.out.println(info[0]+info[1]);
            }
        }catch(Exception e){
            System.out.println("getLastPhpExe exception: "+e.getMessage());
        }
        return lastPhpPid;
    }
}