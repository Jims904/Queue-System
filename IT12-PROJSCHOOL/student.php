<?php
session_start();
include 'db.php';

if(!isset($_SESSION['id']) || $_SESSION['role'] != 'student'){
    header("Location: index.php");
    exit();
}

$msg = "";
$user_id = $_SESSION['id'];

if(isset($_POST['getqueue'])){

    $service_name = $_POST['service'];

    $stmt = mysqli_prepare($conn, "
        SELECT id, name
        FROM services
        WHERE name=?
        LIMIT 1
    ");

    mysqli_stmt_bind_param($stmt, "s", $service_name);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $s_row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if(!$s_row){

        $msg = "Invalid service selected!";

    } else {

        $service_id = $s_row['id'];

        $stmt = mysqli_prepare($conn, "
            SELECT id
            FROM queue
            WHERE student_id=?
            AND status IN ('waiting','in_progress')
            LIMIT 1
        ");

        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);

        $check = mysqli_stmt_get_result($stmt);
        $pending = mysqli_num_rows($check);

        mysqli_stmt_close($stmt);

        if($pending > 0){

            $msg = "You already have a pending queue!";

        } else {

            $stmt = mysqli_prepare($conn, "
                SELECT MAX(queue_number) AS max
                FROM queue
                WHERE DATE(created_at)=CURDATE()
            ");

            mysqli_stmt_execute($stmt);

            $qnum = mysqli_stmt_get_result($stmt);
            $q_row = mysqli_fetch_assoc($qnum);

            mysqli_stmt_close($stmt);

            $queue_number = !empty($q_row['max'])
                ? $q_row['max'] + 1
                : 1;

            $window_number = (($queue_number - 1) % 6) + 1;

            $stmt = mysqli_prepare($conn, "
                INSERT INTO queue
                (
                    user_id,
                    student_id,
                    service_id,
                    queue_number,
                    window_number,
                    status
                )
                VALUES
                (?, ?, ?, ?, ?, 'waiting')
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "iiiii",
                $user_id,
                $user_id,
                $service_id,
                $queue_number,
                $window_number
            );

            $insert = mysqli_stmt_execute($stmt);

            if($insert){

                $msg = "Queue #$queue_number added! Proceed to Window $window_number when called.";

            } else {

                $msg = "Unable to create queue!";

            }

            mysqli_stmt_close($stmt);
        }
    }
}

$stmt = mysqli_prepare($conn, "
    SELECT
        q.*,
        s.name AS service_name
    FROM queue q
    JOIN services s ON q.service_id=s.id
    WHERE q.student_id=?
    AND q.status IN ('waiting','in_progress')
    ORDER BY q.id DESC
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$my_queue_result = mysqli_stmt_get_result($stmt);
$my_queue = mysqli_fetch_assoc($my_queue_result);

mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "
    SELECT
        q.*,
        s.name AS service_name
    FROM queue q
    JOIN services s ON q.service_id=s.id
    WHERE q.status='in_progress'
    ORDER BY q.id ASC
    LIMIT 1
");

mysqli_stmt_execute($stmt);

$now_serving_result = mysqli_stmt_get_result($stmt);
$now_serving = mysqli_fetch_assoc($now_serving_result);

mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "
    SELECT
        h.*,
        s.name AS service_name
    FROM service_history h
    JOIN services s ON h.service_id=s.id
    WHERE h.student_id=?
    ORDER BY h.served_at DESC
    LIMIT 10
");

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$history = mysqli_stmt_get_result($stmt);

mysqli_stmt_close($stmt);

$services = mysqli_query(
    $conn,
    "SELECT id,name FROM services ORDER BY name ASC"
);
?>

<!DOCTYPE html>
<html>
<head>

<title>Student Dashboard</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',Arial;
}

body{
    background:linear-gradient(135deg,#800000 0%,#500000 100%);
    min-height:100vh;
    padding:15px;
}

.container{
    max-width:450px;
    margin:0 auto;
}

.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
    color:white;
}

.header h2{
    font-size:20px;
}

.header a{
    background:white;
    color:#800000;
    padding:8px 15px;
    border-radius:8px;
    text-decoration:none;
    font-weight:bold;
}

.card{
    background:white;
    color:black;
    padding:25px;
    border-radius:15px;
    margin-bottom:15px;
    box-shadow:0 4px 10px rgba(0,0,0,0.3);
}

.card h3{
    color:#800000;
    text-align:center;
    margin-bottom:15px;
    font-size:18px;
}

.card select,
.card button{
    width:100%;
    padding:12px;
    margin:10px 0;
    border-radius:8px;
    border:1px solid #ccc;
    font-size:16px;
}

.card select:focus{
    border-color:#800000;
    outline:none;
}

.card button{
    background:linear-gradient(135deg,#800000,#A52A2A);
    color:white;
    border:none;
    font-weight:bold;
    cursor:pointer;
}

.card button:hover{
    opacity:0.9;
}

.message{
    background:#FFF0E6;
    color:#800000;
    padding:12px;
    border-radius:8px;
    margin-bottom:15px;
    text-align:center;
    font-weight:bold;
}

.queue-number{
    font-size:48px;
    font-weight:bold;
    color:#800000;
    text-align:center;
    margin:10px 0;
}

.window-number{
    font-size:22px;
    font-weight:bold;
    color:#500000;
    text-align:center;
    margin:10px 0;
}

.status{
    text-align:center;
    font-weight:bold;
    margin-top:10px;
}

.waiting{
    color:#D2691E;
}

.in-progress{
    color:#008000;
}

.now-serving{
    background:#FFF0E6;
    border:2px solid #800000;
    text-align:center;
}

.now-serving .queue-number{
    font-size:42px;
}

.history-item{
    border-bottom:1px solid #ddd;
    padding:12px 0;
}

.history-item:last-child{
    border-bottom:none;
}

.history-item strong{
    color:#800000;
}

.history-item small{
    display:block;
    color:#666;
    margin-top:5px;
}

.empty{
    text-align:center;
    color:#777;
    padding:10px;
}

</style>

</head>

<body>



<div class="container">
    

<div class="header">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h2>
    <a href="logout.php">Logout</a>
</div>

<?php if($msg != ""): ?>

<div class="message">
    <?php echo htmlspecialchars($msg); ?>
</div>

<?php endif; ?>


<div class="card">

<h3>Select Service</h3>

<form method="POST">

<select name="service" required>

<option value="">-- Select Service --</option>

<?php while($service = mysqli_fetch_assoc($services)): ?>

<option value="<?php echo htmlspecialchars($service['name']); ?>">
    <?php echo htmlspecialchars($service['name']); ?>
</option>

<?php endwhile; ?>

</select>

<button type="submit" name="getqueue">
    GET QUEUE NUMBER
</button>

</form>

</div>


<?php if($my_queue): ?>

<div class="card">

<h3>My Active Queue</h3>

<div class="queue-number">
    #<?php echo htmlspecialchars($my_queue['queue_number']); ?>
</div>

<div style="text-align:center;">
    <?php echo htmlspecialchars($my_queue['service_name']); ?>
</div>

<div class="window-number">
    WINDOW <?php echo htmlspecialchars($my_queue['window_number']); ?>
</div>

<div class="status <?php echo $my_queue['status'] == 'waiting' ? 'waiting' : 'in-progress'; ?>">
    <?php echo strtoupper(htmlspecialchars($my_queue['status'])); ?>
</div>

</div>

<?php endif; ?>


<div class="card now-serving">

<h3>NOW SERVING</h3>

<?php if($now_serving): ?>

<div class="queue-number">
    #<?php echo htmlspecialchars($now_serving['queue_number']); ?>
</div>

<div>
    <?php echo htmlspecialchars($now_serving['service_name']); ?>
</div>

<div class="window-number">
    WINDOW <?php echo htmlspecialchars($now_serving['window_number']); ?>
</div>

<?php else: ?>

<div class="empty">
    No queue is currently being served.
</div>

<?php endif; ?>

</div>


<div class="card">

<h3>Recent Service History</h3>

<?php if(mysqli_num_rows($history) > 0): ?>

<?php while($row = mysqli_fetch_assoc($history)): ?>

<div class="history-item">

<strong>
    <?php echo htmlspecialchars($row['service_name']); ?>
</strong>

<br>

Queue #<?php echo htmlspecialchars($row['queue_number']); ?>

&nbsp; | &nbsp;

Window <?php echo htmlspecialchars($row['window_number']); ?>

<small>
    <?php echo htmlspecialchars($row['served_at']); ?>
    &nbsp; | &nbsp;
    <?php echo strtoupper(htmlspecialchars($row['status'])); ?>
</small>

</div>

<?php endwhile; ?>

<?php else: ?>

<div class="empty">
    No service history yet.
</div>

<?php endif; ?>

</div>

</div>

</body>
</html>
