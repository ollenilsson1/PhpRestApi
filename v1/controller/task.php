<?php

require_once 'db.php';
require_once '../model/Task.php';
require_once '../model/Response.php';

try {
    $writeDB = DB::connectWriteDB();
    $readDB = DB::connectReadDB();

} catch (PDOException $ex) {
    error_log("Connection error - " . $ex, 0); // Spara felmeddelandet i PHP logfile
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("Database connection error");
    $response->send();
    exit();
}
//Kollar om det finns taskid array key som get variable.
if (array_key_exists("taskid", $_GET)) {
    $taskid = $_GET['taskid'];
    //Validation
    if ($taskid == '' || !is_numeric($taskid)) {
        $response = new Response();
        $response->setHttpStatusCode(400); //Client error
        $response->setSuccess(false);
        $response->addMessage("Task ID cannot be blank or must be numeric");
        $response->send();
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        try { //Inbyggd SQL funktion som gör deadline till rätt date-format.
            $query = $readDB->prepare('SELECT id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") AS deadline, completed FROM tbltasks WHERE id = :taskid');
            $query->bindParam(":taskid", $taskid, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if ($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404); //Not found
                $response->setSuccess(false);
                $response->addMessage("Task not found");
                $response->send();
                exit;
            }

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']); //Använder construct, alla if statements körs från model/task.php
                $taskArray[] = $task->returnTaskAsArray();
            }

            $returnData = array();
            $returnData['rows_returned'] = $rowCount; // Från try högst upp antal rader
            $returnData['tasks'] = $taskArray; // Från while loopen

            $response = new Response();
            $response->setHttpStatusCode(200); // Successful
            $response->setSuccess(true);
            $response->toCache(true); //sparar till cache
            $response->setData($returnData);
            $response->send();
            exit;

        } catch (TaskException $ex) {
            $response = new Response();
            $response->setHttpStatusCode(500); // Server error
            $response->setSuccess(false);
            $response->addMessage($ex->getMessage()); // Returnar error message från model/task.php
            $response->send();
            exit;

        } catch (PDOException $ex) {
            error_log("Database query error - " . $ex, 0); // Spara felmeddelandet i PHP logfile
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to get task");
            $response->send();
            exit();
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    } elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {

    } else {
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Request method not allowed");
        $response->send();
        exit;
    }

}
