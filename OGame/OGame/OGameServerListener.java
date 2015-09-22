import java.io.*;
import java.net.*;


class OGameServerListener extends Thread{
	private int listenPort;
	private OGameServer server;

	public OGameServerListener( int port, OGameServer s ){
		this.listenPort = port;
		this.server = s;
	}

	public void run(){
		while(1==1){
			try{
				

				/*UdpServer updListener = new UdpServer( this.listenPort );
				updListener.run();

				server.setMessage(  updListener.getMessage() );
				server.setSourceIP( updListener.getSourceIP() );
				server.setSourcePort( updListener.getSourcePort() );

				System.out.println(updListener.getSourceIP()+":"+updListener.getSourcePort());*/
				System.out.println("Listener starts listening...");
				while(1==1)
				{
					BufferedReader br = new BufferedReader(new FileReader("Control.txt"));
				    try {
				        StringBuilder sb = new StringBuilder();
				        char firstCh = br.readLine().charAt(0);

			            if( firstCh == '1' && server.state == 0 )
			            {
			            	server.state = 1;
			            }else if( firstCh == '0' && server.state == 1 )
			            {
			            	System.out.println("Terminate process...\n");
			            	Runtime rt = Runtime.getRuntime();
							System.out.println("Taskkill /F /pid "+server.pid);
							rt.exec("Taskkill /F /pid "+server.pid);
			            	server.sProcessReader.close();
							server.sProcessReader = null;
							server.sProcess.destroy();
							server.sProcess = null;
							server.state = 0;
							System.out.println("Process is terminated...");
							PrintWriter writer = new PrintWriter("RealState.txt", "UTF-8");
							writer.println( 0 );
							writer.close();
			            }
				      
				    } catch(Exception e) {
				        br.close();
				    }
				    Thread.sleep(1000);
				}
				/*System.out.println("Listener get msg.");
				if( server.state == 0 ){
					// 0 = idle
					server.state = 1;

					//update server state for website
					PrintWriter writer = new PrintWriter("ServerState.txt", "UTF-8");
					writer.println( server.state );
					writer.close();
					System.out.println("File value: "+server.state);

				}else if( server.state == 1 ){
					// 1 = working

					//force server to stop running plugin
					server.sProcessReader.close();
					server.sProcessReader = null;
					server.sProcess.destroy();
					server.sProcess = null;
					server.state = 0;

					//update server state for website
					PrintWriter writer = new PrintWriter("ServerState.txt", "UTF-8");
					writer.println( server.state );
					writer.close();
					System.out.println("File value: "+server.state);
				}

				Thread.sleep(1000);*/


			}catch(Exception e){
				System.out.println("ServerListener: Receive packet exception: "+e.getMessage());
			}

		}
	}
}