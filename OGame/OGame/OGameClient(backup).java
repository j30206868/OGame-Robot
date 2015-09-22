
public class OGameClient{
	public static void main(String args[]) throws Exception {

		final String ServerIP = "140.120.14.46";
		System.out.println("Sending message to server...");
        for (int i=0; i<100; i++) {

            UdpClient client = new UdpClient(ServerIP, 8080, "msg");
            client.run(); 

        }
        System.out.println("Waiting for reply...");
        UdpServer server = new UdpServer(8081);
        server.run();
        System.out.println("Succeed!"); 
    }
}