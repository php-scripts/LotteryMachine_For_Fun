<?php
/**

/*                         ---------    REQUIREMENT    ----------                                          */
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Please, build a One PHP File Power Draw Machine Application just for fun :)                             //
// 1) Last 10 wining draws should be display on the screen with their previous number of draw              //
// 2) Should have 2 buttons ("Play" and "Export All"). Once you click "Play" a draw run and once you click //
//    the button "Export All" the app will export the last 100 draws as a text file in the same directory  //
//    as the php file.                                                                                     //
// NOTE: USE CLASS INHERITANCE AND SHOULD ONLY HAVE A MAX OF 350 LINES OF CODE INCLUDING COMMENTS & SPACES //
/////////////////////////////////////////////////////////////////////////////////////////////////////////////



/*                            --------  R E A D    M E     F I R S T.  ---------

//////////////////////////////////////////////////////////////////////////////////////////////////////////
////  1. CREATE A MySQL DB, THEN IMPORT THE SQL FILE (draw.sql)                                      /////
////  2. CHANGE THE DB NAME, USERNAME and PASSWORD to MUCH YOUR LOCAL MACHINE.                       /////
////  3. CLICK THE "PLAY" BUTTON TO DRAW.                                                            /////
////  4. CLICL THE "EXCTRACT ALL" BUTTON TO CREATE A CSV FILE (last_100_draw.txt) IN SAME DIRECTORY. /////
//////////////////////////////////////////////////////////////////////////////////////////////////////////
**/

?>



<?php
    //MySQL DB CONNECTION
    $driver = 'mysql';
    $database = "dbname=bet"; //Set your db name
    $server = "$driver:host=localhost;$database";
    $username = 'root'; //Set your db username
    $password = 'root'; //Set your db password

    try {
       $connection = new PDO($server, $username, $password);
       //echo "Database Connected\n";
    }catch(PDOException $e){
       echo $e->getMessage();
       echo " database Connection faild configure DB_name, User and password !!! ";
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <style type="text/css">
            body{
                background: #445599 ;
            }
            input{
                border-radius: 6px;
                padding: 10px 20px;
                margin: 10px 20px;
                font-weight: bold;
            }
            b{
                color: #eeeeff;
            }
            b i{
                color: #ff9999;
            }            
            table{
                color: #aabbee;
            }
            .main_set{
                background: #222222;
                padding: 10px 20px;
                border-radius: 10px;
                webkit-border-radius: 10px;
                moz-border-radius: 10px;
                color: #ff9999;
            }
            .powerball{
                background: #222222;
                padding: 10px 20px;
            }
            .occurence{
                background: #222222;
                padding: 10px 20px;
                border-radius: 50px;
            }
        </style>
    </head>
    <body>
        <form method="post" action="index.php">
            <?php
                function get_buttons(){
                    $var = "";
                    $btns = ["play"=>"PLAY", "extract_all"=>"Extract All"];
                    while (list($x,$y)=each($btns)) {
                        $var .= "<input type=\"submit\" name=\"{$x}\" value=\"{$y}\">";
                    } 
                    return $var;
                }

                echo get_buttons(); 
            ?>  
        </form>
    </body>
</html>



<?php
//Create a draw serie
class LottoDrawMachine{

    protected $powerball;
    protected $max_nbr_ball_in_set; //[40 to 49] nbr of balls in main set //OR// [5 to 49] nbr of balls in powerball set
    protected $min_nbr_ball_in_set; //[40 to 49] nbr of balls in main set //OR// [5 to 49] nbr of balls in powerball set
    protected $min_draw_val; //The serie contain [5 to 7] number of balls drawn from the main set //OR// [0 to 3] nbr of balls drawn from a powerball set
    protected $max_draw_val; //The serie contain [5 to 7] number of balls drawn from the main set //OR// [0 to 3] nbr of balls drawn from a powerball set
    private $main_and_powerbal_set;


    public function draw($max_nbr_ball_in_set, $min_nbr_ball_in_set, $min_draw_val, $max_draw_val){

        $this->max_nbr_ball_in_set = $max_nbr_ball_in_set;
        $this->min_nbr_ball_in_set = $min_nbr_ball_in_set;
        $this->min_draw_val = $min_draw_val;
        $this->max_draw_val = $max_draw_val;

        //Generate an array($set) and shuffle it automatically between the required max/min  
        $set_of_ball = range($this->max_nbr_ball_in_set, $this->min_nbr_ball_in_set);
        shuffle($set_of_ball );

        //Radomize the drawn sirie in order to draw between 5 to 7 main set balls or 0 to 3 powerball(s)
        $randomizer = rand($this->min_draw_val,$this->max_draw_val);
        for($i=0;$i<$randomizer;$i++){
            //Every single shuffeled ball from the array $set will be stored in $serie_of_balls to form a serie of balls
            $serie_of_balls[] = current($set_of_ball);
            next($set_of_ball);
        }
            //Powerball can draw from 0 to 3 which means, in case of 0; we prevent an empty array
            if(empty($serie_of_balls)){
                //No action needed
            }else{
                //$Array $serie_of_balls contain the win draw combination
                return $serie_of_balls;
            }
    }
}
  



//Display current Draw and last 10 draw including number of occurence in previous draw
class drawDisplayer extends LottoDrawMachine{
    
    protected $main_ball_set;
    protected $name_of_set;
    private $out;

    //display_and_persist_sarver() persist the Main-Set and Powerball to a single array $recorded // POWERBALL can be an empty array in case it randomly drawn zero ball.
    function display_and_persist_sarver($name_of_set){
      //We call the db for a global access
      global $connection;

            $this->name_of_set = $name_of_set;
            $this->out = "<table>";

                $this->out .= "<tr>";
                    $this->out .= "<th>" . "Main Set Draw" . "</th>";
                    $this->out .= "<th>" . "Powerball" . "</th>";
                    $this->out .= "<th>" . "Number(s) of Occurance" . "</th>";
                $this->out .= "</tr>";
                

                //Fetch all rows from the record
                function find_all_main_set(){
                    global $connection;

                    $query_all_row = "SELECT main_set FROM lotto";
                    $result_all_row = $connection->prepare($query_all_row);
                    $result_all_row->execute();

                    while($all_rows = $result_all_row->fetch(PDO::FETCH_ASSOC)){
                        $rows[] = $all_rows["main_set"];    
                    }
                    return $rows;
                }

                //$count++ increment when $what exist already in the table 
                function occurance_counter($array, $what) {
                    global $connection;

                    //SQL count numbers of all row in the db
                    $sql_cont = "SELECT count(main_set) FROM lotto";
                    $result_cont = $connection->prepare($sql_cont);
                    $result_cont->execute();
                    $number_of_rows = $result_cont->fetchColumn();

                    //We loop through the whole table to check if any given value exist
                    $count = 0;
                    for ($i = 0; $i < $number_of_rows; $i++) {
                        if ($array[$i] === $what) {
                            $count++;
                        }
                    }
                    return $count;
                }


                //Will display the last 10 draws
                $sql = 'SELECT main_set, powerball FROM lotto ORDER BY recod_date DESC LIMIT 10';
                $stmt = $connection->prepare($sql);
                $stmt->execute();

                while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

                    $this->out .= "<tr>";  
                      //Last 10 Main set from db will reflect here
                        $this->out .= "<td class=\"main_set\">";               
                                        $this->out .= $row['main_set'];
                        $this->out .= "</td>";
                    //Last 10 Powerball from db will reflect here if any exist 
                        $this->out .= "<td class=\"powerball\">";               
                                        $this->out .= $row['powerball'];
                        $this->out .= "</td>";
                    //Amount of time old winning draw occur again. If any duplicated draw exist in the past record
                
                          
                    $all_rows = find_all_main_set();
                    //var_dump($all_rows);
                    $last_ten_draw = $row['main_set'];
     
                    //Check number of time any last 10 draw occure in the all previous draw.
                        $this->out .= "<td class=\"occurence\">";  
                                $this->out .= occurance_counter($all_rows, $last_ten_draw);
                        $this->out .= "</td>";
                }   

                    $this->out .= "</tr>";
                    $this->out .=  "\n";    
                $this->out .= "<b><i>Main Set || </i></b>  <b> // Powerball</b>: <br/>";

                        if(!empty($this->name_of_set["main"])){
                            foreach ($this->name_of_set["main"] as $key){
                                $saved_main[] = $key;
                                $this->out .= "<b><i> &nbsp {$key}  </i></b>";
                                //Insterting into the db
                            }
                        }
                    
                        if(!empty($this->name_of_set["power"])){
                            foreach ($this->name_of_set["power"] as $name){
                                if(empty($name)){
                                    $recorded_power[] = null;
                                }else{
                                    $recorded_power[] = $name;                
                                    //Insterting into the db
                                    $this->out .= " <b> &nbsp {$name}</b>";
                                }
                            }
                        }
                    $this->out .= "<br/>";
                    $this->out .= "<br/>";

            $this->out .= "</table>";
            echo $this->out;

            //Turn the array into a string for record saving
            $insert_main = implode(" ", $saved_main);
            if(!empty($recorded_power)){
                $insert_power = implode(" ", $recorded_power);
            }else{
                $insert_power = null;
            } 


        if(isset($_POST['play'])) {
            $msg = "";
            $sql_insert = "INSERT INTO lotto(main_set, powerball)
                           VALUES('" . $insert_main . "', '" . $insert_power ."')";
         
            if($connection->exec($sql_insert) === false){
                $msg = 'Error inserting the department.';
                return false;
            }else{
                $msg = "The new record have been created";
                return true;
            }
        }
    }



        //We set 4 argument to drow() to assign min/max set and min/max draw
        function setter(){

            $main_ball_setter = $this->draw(
                                        $a = 40, 
                                        $b = 49, 
                                        $c = 5, 
                                        $d = 7
                                        );
            $powerball_setter = $this->draw(
                                        $w = 5, 
                                        $x = 49, 
                                        $y = 0, 
                                        $z = 3
                                        );

            $main_and_powerbal_getter = [
                                    "main" => $main_ball_setter, 
                                    "power" => $powerball_setter
                                    ];

            $this->main_and_powerbal_getter = $main_and_powerbal_getter;
            $this->main_ball_setter = $main_ball_setter;
            $this->powerball_setter = $powerball_setter;


                $record = $this->display_and_persist_sarver($this->main_and_powerbal_getter);
        }
}


//EXTRACT ALL LAST 100 DRAWS INTO A CSV FILE
if(isset($_POST["extract_all"])){
    $file = fopen("last_100_draw.txt", "w") or die("Unable to open file!");

    // Sample data. This can be fetched from mysql too
    //Will display the last 100 draws
    $sql = 'SELECT main_set, powerball FROM lotto ORDER BY recod_date DESC LIMIT 100';
    $data = $connection->prepare($sql);
    $data->execute();  

    //Show every draw
    foreach ($data as $row)
    {
        $text = $row['main_set'] . " : " .  $row['powerball'] . "\n";
    fwrite($file, $text);
    }
    
    fclose($file);
}

 $get = new drawDisplayer();
 $get->setter();
 $save = new drawDisplayer()
 //$save->saving();
?>