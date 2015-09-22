import java.io.*;
import java.net.*;
import java.lang.management.ManagementFactory;

public class TimeSync extends Thread {
    public Process sProcess;
    public BufferedReader sProcessReader;

    //used by main
    private static final int ServerPort = 8080;
 
    public static void main(String args[]) throws Exception {

        //init server and listener
        System.out.println("Start TimeSync.java...");                   
    }

    public void run() {

        while(1==1){

            System.out.println("Start run OneTimeTimer.bat...");
            try{
                Runtime rt = Runtime.getRuntime();
                //this.sProcess = rt.exec("GuardEternal.bat");
                this.sProcess = rt.exec("OneTimeTimer.bat");

                Process pr = this.sProcess;

                BufferedReader input = new BufferedReader(new InputStreamReader(pr.getInputStream()));
                this.sProcessReader = input;

                String line=null;

                System.out.println( "========================= OneTimeTimer.bat Start =========================");

                while((line=input.readLine()) != null) {
                    System.out.println(line);
                }

                int exitVal = pr.waitFor();

                System.out.println( "========================= OneTimeTimer.bat end =========================");
                
            }catch(Exception e){
                this.writeLog("Execute bat failed: "+e.getMessage(), true);
            }

            try{
                Thread.sleep( 10*60*1000); 
            }catch(InterruptedException e){
                System.out.println("Server thread sleep(1000) failed: "+e.getMessage());
            }
           
        }   
        
    }

}