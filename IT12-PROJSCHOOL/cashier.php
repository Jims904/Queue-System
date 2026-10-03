<?php
session_start();
include 'db.php';

if(!isset($_SESSION['id']) || $_SESSION['role'] !== 'cashier'){
    header("Location: admin_login.php");
    exit();
}

$cashier_id = (int)$_SESSION['id'];
$msg = "";

$stmt = mysqli_prepare($conn, "
    SELECT id, name, window_number
    FROM users
    WHERE id=? AND role='cashier'
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $cashier_id);
mysqli_stmt_execute($stmt);
$cashier_result = mysqli_stmt_get_result($stmt);
$cashier = mysqli_fetch_assoc($cashier_result);
mysqli_stmt_close($stmt);

if(!$cashier){
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

$cashier_name = $cashier['name'];
$window_number = (int)$cashier['window_number'];

if($window_number < 1 || $window_number > 6){
    $msg = "Your cashier window is not configured.";
}

if(isset($_GET['call']) && $window_number >= 1 && $window_number <= 6){

    $stmt = mysqli_prepare($conn, "
        SELECT id
        FROM queue
        WHERE status='in_progress'
        AND window_number=?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $window_number);
    mysqli_stmt_execute($stmt);
    $active_result = mysqli_stmt_get_result($stmt);
    $active = mysqli_fetch_assoc($active_result);
    mysqli_stmt_close($stmt);

    if(!$active){

        $stmt = mysqli_prepare($conn, "
            SELECT id
            FROM queue
            WHERE status='waiting'
            AND window_number=?
            ORDER BY queue_number ASC, id ASC
            LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt, "i", $window_number);
        mysqli_stmt_execute($stmt);
        $next_result = mysqli_stmt_get_result($stmt);
        $next = mysqli_fetch_assoc($next_result);
        mysqli_stmt_close($stmt);

        if($next){
            $stmt = mysqli_prepare($conn, "
                UPDATE queue
                SET status='in_progress'
                WHERE id=?
                AND window_number=?
                AND status='waiting'
            ");
            mysqli_stmt_bind_param($stmt, "ii", $next['id'], $window_number);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    header("Location: cashier.php");
    exit();
}

if(isset($_GET['serve']) && $window_number >= 1 && $window_number <= 6){

    $queue_id = (int)$_GET['serve'];

    $stmt = mysqli_prepare($conn, "
        SELECT
            id,
            student_id,
            service_id,
            queue_number,
            window_number
        FROM queue
        WHERE id=?
        AND window_number=?
        AND status='in_progress'
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "ii", $queue_id, $window_number);
    mysqli_stmt_execute($stmt);
    $queue_result = mysqli_stmt_get_result($stmt);
    $queue = mysqli_fetch_assoc($queue_result);
    mysqli_stmt_close($stmt);

    if($queue){

        $stmt = mysqli_prepare($conn, "
            UPDATE queue
            SET status='served'
            WHERE id=?
            AND window_number=?
            AND status='in_progress'
        ");
        mysqli_stmt_bind_param($stmt, "ii", $queue['id'], $window_number);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $status = 'served';

        $stmt = mysqli_prepare($conn, "
            INSERT INTO service_history
            (
                student_id,
                service_id,
                queue_number,
                window_number,
                status
            )
            VALUES(?,?,?,?,?)
        ");
        mysqli_stmt_bind_param(
            $stmt,
            "iiiis",
            $queue['student_id'],
            $queue['service_id'],
            $queue['queue_number'],
            $queue['window_number'],
            $status
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("Location: cashier.php");
    exit();
}

$waiting = 0;
$progress = 0;
$completed = 0;
$current = null;
$next_queue = null;

if($window_number >= 1 && $window_number <= 6){

    $stmt = mysqli_prepare($conn, "
        SELECT COUNT(*) AS total
        FROM queue
        WHERE status='waiting'
        AND window_number=?
    ");
    mysqli_stmt_bind_param($stmt, "i", $window_number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $waiting = (int)$row['total'];
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "
        SELECT COUNT(*) AS total
        FROM queue
        WHERE status='in_progress'
        AND window_number=?
    ");
    mysqli_stmt_bind_param($stmt, "i", $window_number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $progress = (int)$row['total'];
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "
        SELECT COUNT(*) AS total
        FROM queue
        WHERE status='served'
        AND window_number=?
        AND DATE(created_at)=CURDATE()
    ");
    mysqli_stmt_bind_param($stmt, "i", $window_number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $completed = (int)$row['total'];
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "
        SELECT
            q.id,
            q.queue_number,
            q.window_number,
            q.status,
            u.name,
            s.name AS service_name
        FROM queue q
        JOIN users u ON q.student_id=u.id
        JOIN services s ON q.service_id=s.id
        WHERE q.status='in_progress'
        AND q.window_number=?
        ORDER BY q.id ASC
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $window_number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $current = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "
        SELECT
            q.id,
            q.queue_number,
            q.window_number,
            u.name,
            s.name AS service_name
        FROM queue q
        JOIN users u ON q.student_id=u.id
        JOIN services s ON q.service_id=s.id
        WHERE q.status='waiting'
        AND q.window_number=?
        ORDER BY q.queue_number ASC, q.id ASC
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $window_number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $next_queue = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

function e($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cashier Window <?php echo e($window_number); ?></title>

<style>
*{
    box-sizing:border-box;
    margin:0;
    padding:0;
}

body{
    font-family:Arial,Helvetica,sans-serif;
    background:#f5efe6;
    color:#3b2418;
}

.header{
    background:#7b1e1e;
    color:white;
    padding:18px 30px;
    display:flex;
    align-items:center;
    gap:20px;
}

.header-left{
    flex:1;
}

.header-title{
    font-size:27px;
    font-weight:bold;
}

.header-subtitle{
    margin-top:5px;
    font-size:14px;
    opacity:.9;
}

.window-badge{
    background:#f3d6a4;
    color:#641717;
    padding:12px 24px;
    border-radius:10px;
    font-size:22px;
    font-weight:bold;
    white-space:nowrap;
}

.logout{
    background:white;
    color:#7b1e1e;
    text-decoration:none;
    padding:11px 20px;
    border-radius:8px;
    font-weight:bold;
    white-space:nowrap;
}

.logout:hover{
    background:#f2dddd;
}

.container{
    width:92%;
    max-width:1100px;
    margin:30px auto;
}

.message{
    background:#fff4d6;
    border:1px solid #e1c37a;
    padding:14px 18px;
    border-radius:10px;
    margin-bottom:20px;
    color:#704c00;
    font-weight:bold;
}

.stats{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:18px;
    margin-bottom:25px;
}

.stat{
    background:white;
    border-radius:14px;
    padding:22px;
    text-align:center;
    box-shadow:0 3px 12px rgba(0,0,0,.08);
}

.stat-title{
    font-size:14px;
    color:#777;
    margin-bottom:8px;
}

.stat-number{
    font-size:38px;
    font-weight:bold;
    color:#7b1e1e;
}

.main-grid{
    display:grid;
    grid-template-columns:1.4fr 1fr;
    gap:22px;
}

.card{
    background:white;
    border-radius:15px;
    padding:25px;
    box-shadow:0 3px 12px rgba(0,0,0,.08);
}

.card-title{
    font-size:20px;
    font-weight:bold;
    color:#7b1e1e;
    margin-bottom:20px;
}

.now-serving{
    text-align:center;
    border:3px solid #7b1e1e;
}

.queue-number{
    font-size:80px;
    font-weight:bold;
    color:#7b1e1e;
    margin:10px 0;
}

.student-name{
    font-size:22px;
    font-weight:bold;
    margin-bottom:8px;
}

.service-name{
    font-size:16px;
    color:#666;
}

.empty{
    padding:35px 10px;
    text-align:center;
    color:#888;
}

.next-box{
    background:#f8f1e8;
    padding:18px;
    border-radius:10px;
    margin-bottom:20px;
}

.next-number{
    font-size:45px;
    font-weight:bold;
    color:#7b1e1e;
}

.next-name{
    font-weight:bold;
    margin-top:5px;
}

.button{
    display:block;
    width:100%;
    padding:15px;
    border:none;
    border-radius:9px;
    font-size:17px;
    font-weight:bold;
    text-decoration:none;
    text-align:center;
    cursor:pointer;
}

.call{
    background:#7b1e1e;
    color:white;
}

.call:hover{
    background:#5f1515;
}

.serve{
    background:#47723c;
    color:white;
    margin-top:12px;
}

.serve:hover{
    background:#365b2e;
}

.disabled{
    background:#ccc;
    color:#666;
    cursor:not-allowed;
}

.window-info{
    margin-top:25px;
    background:#7b1e1e;
    color:white;
    border-radius:15px;
    padding:22px;
    text-align:center;
}

.window-info-title{
    font-size:14px;
    opacity:.85;
}

.window-info-number{
    font-size:42px;
    font-weight:bold;
    margin-top:5px;
}

@media(max-width:750px){
    .header{
        padding:15px;
        flex-wrap:wrap;
    }

    .header-left{
        width:100%;
        flex-basis:100%;
    }

    .window-badge{
        font-size:18px;
        padding:10px 16px;
    }

    .logout{
        padding:10px 15px;
    }

    .stats{
        grid-template-columns:1fr;
    }

    .main-grid{
        grid-template-columns:1fr;
    }

    .queue-number{
        font-size:65px;
    }

    .container{
        width:94%;
    }
}
</style>
</head>

<body>

<div class="header">

    <div class="header-left">
        <div class="header-title">Cashier Portal</div>
        <div class="header-subtitle">
            Welcome, <?php echo e($cashier_name); ?>
        </div>
    </div>

    <div class="window-badge">
        WINDOW <?php echo e($window_number); ?>
    </div>

    <a href="logout.php" class="logout">Logout</a>

</div>

<div class="container">

<?php if($msg): ?>
    <div class="message"><?php echo e($msg); ?></div>
<?php endif; ?>

<div class="stats">

    <div class="stat">
        <div class="stat-title">WAITING</div>
        <div class="stat-number"><?php echo $waiting; ?></div>
    </div>

    <div class="stat">
        <div class="stat-title">IN PROGRESS</div>
        <div class="stat-number"><?php echo $progress; ?></div>
    </div>

    <div class="stat">
        <div class="stat-title">SERVED TODAY</div>
        <div class="stat-number"><?php echo $completed; ?></div>
    </div>

</div>

<div class="main-grid">

    <div class="card now-serving">

        <div class="card-title">NOW SERVING</div>

        <?php if($current): ?>

            <div class="queue-number">
                <?php echo e($current['queue_number']); ?>
            </div>

            <div class="student-name">
                <?php echo e($current['name']); ?>
            </div>

            <div class="service-name">
                <?php echo e($current['service_name']); ?>
            </div>

            <a
                href="cashier.php?serve=<?php echo e($current['id']); ?>"
                class="button serve"
            >
                SERVE / COMPLETE
            </a>

        <?php else: ?>

            <div class="empty">
                No student is currently being served.
            </div>

            <?php if($next_queue): ?>

                <a
                    href="cashier.php?call=1"
                    class="button call"
                >
                    CALL NEXT
                </a>

            <?php else: ?>

                <div class="button disabled">
                    NO QUEUE
                </div>

            <?php endif; ?>

        <?php endif; ?>

    </div>

    <div class="card">

        <div class="card-title">NEXT QUEUE</div>

        <?php if($next_queue): ?>

            <div class="next-box">

                <div class="next-number">
                    #<?php echo e($next_queue['queue_number']); ?>
                </div>

                <div class="next-name">
                    <?php echo e($next_queue['name']); ?>
                </div>

                <div class="service-name">
                    <?php echo e($next_queue['service_name']); ?>
                </div>

            </div>

            <?php if(!$current): ?>

                <a
                    href="cashier.php?call=1"
                    class="button call"
                >
                    CALL NEXT QUEUE
                </a>

            <?php else: ?>

                <div class="button disabled">
                    FINISH CURRENT QUEUE FIRST
                </div>

            <?php endif; ?>

        <?php else: ?>

            <div class="empty">
                No waiting queue for Window <?php echo e($window_number); ?>.
            </div>

        <?php endif; ?>

    </div>

</div>

<div class="window-info">

    <div class="window-info-title">
        YOU ARE ASSIGNED TO
    </div>

    <div class="window-info-number">
        WINDOW <?php echo e($window_number); ?>
    </div>

</div>

</div>

</body>
</html>
