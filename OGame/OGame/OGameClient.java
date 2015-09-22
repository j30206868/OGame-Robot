import java.awt.*;
import java.awt.event.*;
import javax.swing.JButton;
import javax.swing.JFrame;
import javax.swing.SwingUtilities;
import java.net.SocketTimeoutException;

public class OGameClient implements WindowListener, Runnable, ActionListener {
    //attribute
    private int state = 0;
    private int windowX;
    private int windowY;
    private int windowWidth;
    private int windowHeight;
    private final String ServerIP = "140.120.14.46";
    private final int ServerPort = 8080;
    private int ServerReplyPort = 8080;
    private int receiverTimeOut = 5;

    //Object
    private TextField textField = null;
    private JButton runBtn = null;
    private JFrame frame = null;

    //
    private UdpServer replyListener;

    //
    public boolean isCreated = false;

    //main
    public static void main(String args[]) throws Exception {

        OGameClient OGClient = new OGameClient();
        // Schedules the application to be run at the correct time in the event queue.
        SwingUtilities.invokeLater(OGClient);
    }

    public OGameClient(){

        Dimension screenSize = Toolkit.getDefaultToolkit().getScreenSize();

        this.windowWidth = screenSize.width/4;
        this.windowHeight = screenSize.height/10;

        this.windowX = Math.max(0, (screenSize.width  - windowWidth ) / 2);
        this.windowY = Math.max(0, (screenSize.height - windowHeight ) / 2);

    }

    @Override
    public void run() {
        // Create the window
        this.frame = new JFrame("OGame Plugin");

        //init window pos and size
        Dimension screenSize = Toolkit.getDefaultToolkit().getScreenSize();
        frame.setLocation(this.windowX, this.windowY);
        frame.setSize(this.windowWidth, this.windowHeight);
        frame.validate();  // Make sure layout is ok

        // Sets the behavior for when the window is closed
        frame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        // Add a layout manager so that the button is not placed on top of the label
        frame.setLayout(new FlowLayout());
        // Add a label and a button

        this.textField = new TextField(25);
        //this.textField.setEnabled(false);
        this.textField.setText(" Server: "+this.ServerIP+":"+this.ServerPort);
        this.textField.setForeground(Color.BLUE);

        this.runBtn = new JButton("Run");

        runBtn.addActionListener(this);
        frame.add(runBtn);
        frame.add(textField);
       
        // Arrange the components inside the window
        //f.pack();
        // By default, the window is not visible. Make it visible.
        frame.setVisible(true);
    }

    public void actionPerformed(ActionEvent e) {

        try{
            this.textField.setForeground(Color.BLUE);
            //send msg and receive the server's reply
            String replyMsg = this.getConnection();

            if( replyMsg.equals("Working!") ){

                textField.setText(" State: The plugin is working now.");
                runBtn.setText("Stop");
                this.state = 1;

            }else if( replyMsg.equals("Stopped!") ){

                textField.setText(" State: The plugin is stopped now!");
                runBtn.setText("Run");
                this.state = 0;

            }

        }catch(SocketTimeoutException error){

            this.textField.setForeground(Color.RED);

            // receive time out
            if(this.state == 0){
                textField.setText(" No reply, server is not working.");
            }else if(this.state == 1){
                textField.setText(" No reply, server can't be stopped.");
            }

        }catch(Exception error){

            this.textField.setForeground(Color.RED);

            textField.setText(" ERROR: "+error.getMessage());
        }

        this.cleanSocket();
    }

    public String getConnection() throws Exception{
        textField.setText(" Sending message to server...");

       // for (int i=0; i<100; i++) {

            UdpClient client = new UdpClient(this.ServerIP, this.ServerPort, "msg");
            client.run(); 
            this.ServerReplyPort = client.getConntedPort();
            System.out.println("ServerReplyPort: "+ServerReplyPort);

      //  }             

        textField.setText(" Waiting for server's reply...");
        this.replyListener = new UdpServer( this.ServerReplyPort );
        this.replyListener.setTimeLimit( this.receiverTimeOut );
        replyListener.run();

        return this.replyListener.getMessage();
    }

    public void cleanSocket(){
        this.replyListener.close();
        this.replyListener = null;
    }

    public void windowClosing(WindowEvent e) {
        this.replyListener.close();
        this.frame.dispose();
        System.exit(0);
    }

    public void windowOpened(WindowEvent e) {}
    public void windowActivated(WindowEvent e) {}
    public void windowIconified(WindowEvent e) {}
    public void windowDeiconified(WindowEvent e) {}
    public void windowDeactivated(WindowEvent e) {}
    public void windowClosed(WindowEvent e) {}
}