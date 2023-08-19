<?php 
$paPDO = initDB();
$paSRID = '4326';

function initDB()
{
    // Kết nối CSDL
    $paPDO = new PDO('pgsql:host=localhost;dbname=railway_vietnam;port=5432', 'postgres', '123456');
    return $paPDO;
}
function query($paPDO, $paSQLStr)
{
    try
    {
        // Khai báo exception
        $paPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Sử đụng Prepare 
        $stmt = $paPDO->prepare($paSQLStr);
        // Thực thi câu truy vấn
        $stmt->execute();
        
        // Khai báo fetch kiểu mảng kết hợp
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        
        // Lấy danh sách kết quả
        $paResult = $stmt->fetchAll();   
        return $paResult;                 
    }
    catch(PDOException $e) {
        echo "Thất bại, Lỗi: " . $e->getMessage();
        return null;
    }       
}
function closeDB($paPDO)
{
    // Ngắt kết nối
    $paPDO = null;
}
if (isset($_POST['name'])) {
    $name = $_POST['name'];
    $aResult = seacherStation($paPDO, $paSRID, $name);
    echo $aResult;
}
//tim kiem ga tau
function seacherStation($paPDO, $paSRID, $name)
{

    $mySQLStr = "SELECT ST_AsGeoJson(geom) as geo from ga where ten_ga like '$name'";
    $result = query($paPDO, $mySQLStr);

    if ($result != null) {
        // Lặp kết quả
        foreach ($result as $item) {
            return $item['geo'];
        }
    } else
        return "null";
}
