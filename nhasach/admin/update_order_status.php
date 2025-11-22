<?php
header("Content-Type: application/json");

include_once "../config/connect.php";
include_once "../models/OrderModel.php";

$data = json_decode(file_get_contents("php://input"), true);

$order_id = $data['order_id'];
$status = $data['status'];

$model = new OrderModel($conn);

if ($model->updateStatus($order_id, $status)) {
    echo json_encode(["success" => true, "message" => "Cập nhật thành công"]);
} else {
    echo json_encode(["success" => false, "message" => "Cập nhật thất bại"]);
}
?>