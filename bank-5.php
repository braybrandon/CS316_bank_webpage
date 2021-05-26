
<?php

/*
 * bank-3.php is a bank account file that utilizes front-end jquery with ajax to do all of its 
 * back-end calls. Whenever the user loads the page the jquery does an ajax call to the server
 * requesting for the bank account information. The bank account information is stored via MySQL
 * in a table called bankAccount. The table has 3 columns, an id column, a checking column, and a 
 * savings column. The checking column holds the amount that's in the persons checking account. The
 * savings column holds the amount that's in the persons savings account. After the query for the 
 * account information the server encodes the data into JSON and sends it back to the browser. The
 * browser then decodes the information and displays the account information to the user. The user 
 * has 4 options they can either deposit into checking, deposit into savings, transfer from
 * checking to savings, or transfer from savings into checking. When the user decides to enter
 * information in any 4 of these input fields the browser verifies the information is a valid 
 * numerical number and that it is not negative as well as overdraw the account. Then the browser 
 * will do a post call to update the database without reloading the browser. Then the browser 
 * request for the new account information to update the table on the webpage. There is also a 
 * reset button for testing purposes that will reset the database back to the original bank account
 * values of 100 in checking and 1000 in savings. The reset would not be used during deployment.
 */

require_once './db_creds.inc';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
try {
	//getPDO();
	$PDO = new PDO(K_CONNECTION_STRING, K_USERNAME, K_PASSWORD);
	$PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exeption $ex) {
	echo $ex->getMessage();
}
function deposit($account, $amount) {
	return $account + $amount;

}
function withdraw($account, $amount) {
	return $account - $amount;
}
function retrieveData() {
	global $PDO;
	$sql = "SELECT checking, savings FROM bankAccount WHERE id='1'";
	try {
		$stmt = $PDO->query($sql);
		$account = $stmt->fetch();
	} catch (Exception $ex) {
		echo $ex->getMessage();
	}
	return $account;
}
function createTable() {
	global $PDO;
	$sql = "CREATE TABLE IF NOT EXISTS bankAccount (
		id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		checking decimal(15,2) NOT NULL,
		savings decimal(15,2) NOT NULL,
		reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)";
	try {
		$PDO->exec($sql);
		$newData = "INSERT INTO bankAccount (id, checking, savings)
			VALUES(1, 100.00, 1000.00) ON DUPLICATE KEY  UPDATE checking = checking + 0";
		$PDO->exec($newData);
	} catch(Exception $ex) {
		echo $ex->getMessage();	
	}
}
function updateTable($account) {
	global $PDO;
	try {
		$sql = " UPDATE bankAccount SET checking = " . $account["checking"] 
			. ", savings = " . $account["savings"] . " WHERE id = 1";
		$stmt = $PDO->prepare($sql);
		$stmt->execute();
	} catch (Exception $ex) {
		echo $ex->getMessage();	
	}
}
function testInput($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	if(is_numeric($data)) {
		if($data >= 0) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}
function testTransfer($data, $account) {
	if(testinput($data)) {
		if($data <= $account) {
			return true;
		}else {
			return false;
		}	
	} else {
		return false;
	}

}
createTable();

//if the server requested using a post method then the server finds out what the server is requesting
if($_SERVER["REQUEST_METHOD"] == "POST") {
	$account = retrieveData();
	if(!empty($_POST["getData"])) {
		$myJSON = json_encode($account);
		echo $myJSON;
		return;
	}
	if(!empty($_POST["reset"])) {
		$account["checking"] = 100.00;
		$account["savings"] = 1000.00;

	}
	if(!empty($_POST["dChecking"])) {
		if(testInput($_POST["dChecking"])) {
			$account["checking"] = 
				deposit($account["checking"], $_POST["dChecking"]);
		}
	}
	if(!empty($_POST["dSavings"])) {
		if(testInput($_POST["dSavings"])) {
			$account["savings"] = 
				deposit($account["savings"], $_POST["dSavings"]);
		}
	}
	if(!empty($_POST["wChecking"])) {
		if(testTransfer($_POST["wChecking"], $account["checking"])) {
			$account["checking"] = 
				withdraw($account["checking"], $_POST["wChecking"]);
		}
	}
	if(!empty($_POST["wSavings"])) {
		if(testTransfer($_POST["wSavings"], $account["savings"])) {
			$account["savings"] = 
				withdraw($account["savings"], $_POST["wSavings"]);
		}
	}
	if(!empty($_POST["tChecking"])) {
		if(testTransfer($_POST["tChecking"], $account["checking"])) {
			$account["savings"] = 
				deposit($account['savings'], $_POST["tChecking"]);
			$account["checking"] = 
				withdraw($account["checking"], $_POST["tChecking"]);
		}
	}
	if(!empty($_POST["tSavings"])) {
		if(testTransfer($_POST["tSavings"], $account["savings"])) {
			$account["checking"] = 
				deposit($account["checking"], $_POST["tSavings"]);
			$account["savings"] = 
				withdraw($account["savings"], $_POST["tSavings"]);
		}
	}
	updateTable($account);
	return;
}


?>
<!DOCTYPE html>
<html lang="en">
	<head>
	<meta charset="UTF-8">
	<title>First Bank of HTML</title>	
	<link rel="stylesheet" 
		href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" 
		integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" 
		crossorigin="anonymous">
	<link rel="stylesheet"
	href="https://cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
	<script
	src="https://code.jquery.com/jquery-3.6.0.min.js"
	integrity="sha384-vtXRMe3mGCbOeY7l30aIg8H9p3GdeSe4IFlP6G8JMa7o7lXvnz3GFKzPxzJdPfGK"
	crossorigin="anonymous"></script>	
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" 
		integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" 
		crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js" 
		integrity="sha384-+YQ4JLhjyBLPDQt//I+STsc9iw4uQqACwlvpslubQzn4u2UU2UFM80nGisd026JF" 
		crossorigin="anonymous"></script>
<script
	src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js">
	</script> <!-- uses the CloudFare content-delivery network -->
<script>


const myDBName = 'bankDB';
var myDB;
const serial = 3;

function validateDeposit(num) {
	if (num == "") {
		return false;
	} else if (isNaN(num)) {
		alert(num + " is not a valid integer");
		return false;	
	} else if (num < 0){
		alert("Can not deposit a negative value");
		return false;
	} else {
		return true;
	}	
}
function validateTransfer(transfer, account) {
	if (transfer == "") {
		return false;
	} else if (isNaN(transfer)) {
		alert(transfer + " is not a valid integer");
		return false;	
	} else if (transfer < 0) {
		alert("Can not transfer a negative value");
		return false;

	}else if (parseFloat(transfer)	> parseFloat(account)){
		alert("Cannot transfer $" + transfer + " You only have $" + account + " in your account.");
		return false;
	} else {
		return true;
	}	
}
function validateWithdraw(transfer, ammount) {
		if (transfer == "") {
			return false;
		} else if (isNaN(transfer)) {
			alert(transfer + " is not a valid integer");
			return false;	
		} else if (transfer < 0) {
			alert("Can not withdraw a negative value");
			return false;

		}else if (parseFloat(transfer)	> parseFloat(ammount)){
			alert("Cannot withdraw $" + transfer + " You only have $" + ammount + " in your account.");
			return false;
		} else {
			return true;
		}	
}
function withdraw(action, acount, relation) {
	
		var myData;	
	$.ajax({method: "POST", url: "bank-3.php", data: { getData: " " }}).done(function(response, status) {
		myData = JSON.parse(response);
		if (action == "wChecking"){
			if(validateWithdraw(acount, myData.checking)) {
				postBalance(action, acount);
				var dest = action.substring(1);
				$("#withdrawForm")[0].reset();
				$(".nav-pills a[href='#content-1']").tab("show");
				var entry = {type: 'withdraw', source: dest, destination: "external", amount: acount}; 
				addEntry(relation, entry);
				displayTable(relation);
			}
		}
		else
		{
				if(validateWithdraw(acount, myData.savings)){
					postBalance(action, acount);
					var dest = action.substring(1);
					$("#withdrawForm")[0].reset();
					$(".nav-pills a[href='#content-1']").tab("show");
					var entry = {type: 'withdraw', source: dest, destination: "external", amount: acount}; 
					addEntry(relation, entry);
					displayTable(relation);
				}
		}
	});
}
function clearInput(input) {
	document.forms["myForm"][input].value = null;
}
function getAccountBalance() {

	$.ajax({method: "POST", url: "bank-3.php", data: { getData: " " }}).done(function(response, status) {
		var myData = JSON.parse(response);
		$("#checking").text(myData.checking);
		$("#savings").text(myData.savings);
	});
}
function postBalance(action, amount) {
	var myObj = {};
	myObj[action] = amount;
	$.post("bank-5.php", myObj, function() {
		getAccountBalance();
	});

}
function transferBalance(action, dest, amount, isChecking, relation) {
	var myData;	
	$.ajax({method: "POST", url: "bank-3.php", data: { getData: " " }}).done(function(response, status) {
		myData = JSON.parse(response);
		if(isChecking) {
			if(validateTransfer(amount, myData.checking)) {
				postBalance(action, amount);
				var entry = {type: 'transfer', source: action.substring(1), destination: dest.substring(1), amount: amount}; 
				addEntry(relation, entry);
				displayTable(relation);
		$("#transferForm")[0].reset();
		$(".nav-pills a[href='#content-1']").tab("show");
			}
		} else {
			if(validateTransfer(amount, myData.savings)) {
				postBalance(action, amount);
				var entry = {type: 'transfer', source: action.substring(1), destination: dest.substring(1), amount: amount}; 
				addEntry(relation, entry);
				displayTable(relation);
				$("#transferForm")[0].reset();
				$(".nav-pills a[href='#content-1']").tab("show");
			}
		}
	});
}
function getMonthlyInterest(r) {
	return r/12;
}
function getCost(m, years) {
	return (1 + m/ 100) ** (12 * years);
}
function getMonthlyPayment(p, r, years) {
	var m = getMonthlyInterest(r);
	var c = getCost(m, years);
	var payment = p * (m / 100) * c / (c - 1);
	return Math.round(payment * 100) / 100;
}

function initDB(relation) {
	let request = window.indexedDB.open(myDBName, serial);
	request.onerror = function(event) {
		alert('Error loading database: ' + request.error.message);
	}
	request.onsuccess = function(event) {
		console.log('Success loading database.');
		myDB = event.target.result;
		myDB.onerror = function(event) {
			alert('error: ' + event.target.error.message);
		};
		displayTable(relation);
	};
	request.onupgradeneeded = function(event) {
		console.log('Upgrading database.');
		myDB = request.result;
		myDB.onerror = function(event) {
			alert('error: ' + event.target.error.message);
		};
		const table = myDB.createObjectStore(relation, {autoIncrement: true});
		table.createIndex("type", "type", { unique: false });
		table.createIndex("source", "source", { unique: false });
		table.createIndex("destination", "destination", { unique: false });
		table.createIndex("amount", "amount", { unique: false });

	};
	return('initialized');

}

function displayRow(entry) {
	answer = [];
	Object.keys(entry).forEach(function(col) {
		answer.push(entry[col]);
	});
	console.log(answer);
	return answer;
}

function displayTable(relation) {
	var data = [];
	dtable.clear();
	const table = myDB.transaction( [relation], "readonly").objectStore(relation);
	table.openCursor().onsuccess = function(event) {
		const cursor = event.target.result;
		if(cursor) {
			var answer = displayRow(cursor.value);
			dtable.row.add(answer).draw();
			cursor.continue();
		} else {
			console.log('end of display for table ' + relation);
			console.log(data);
		}
	}
}

function addEntry(relation, entry) {
	var transaction = myDB.transaction([relation], "readwrite");
	var table = transaction.objectStore(relation);
	var request = table.add(entry);
	request.onsuccess = function() {
		console.log('successfully added entry');
	};
}

function deleteDB(relation) {
	if (myDB) {
		myDB.close();
		myDB = null;
	};
	window.indexedDB.deleteDatabase(myDBName);
	console.log('Database deleted');
}

var dtable;

$(document).ready(function(){
	var relation = 'bankAccount';
	$('[data-toggle="tooltip"]').tooltip();
	$("#construction").css("background-color", "yellow");
	getAccountBalance();
	$("th").css("text-align", "center");
	$("td").css("text-align", "center");
	$("#monthlyPayment").hide();
	dtable = $("#transaction").DataTable();
	initDB(relation);
	$("#depositForm").on("submit", function() {
		event.preventDefault();
		var checkingDeposit = document.forms["depositForm"]["dChecking"].value;
		var account = document.forms["depositForm"]["account"].value;
		var dest = account.substring(1);
		if(validateDeposit(checkingDeposit)) {
			postBalance(account, checkingDeposit);
		}
		$("#depositForm")[0].reset();
		$(".nav-pills a[href='#content-1']").tab("show");
		var entry = {type: 'deposit', source: 'external', destination: dest, amount: checkingDeposit}; 
		addEntry(relation, entry);
		displayTable(relation);
	});
	$("#withdrawForm").on("submit", function() {
		event.preventDefault();
		var checkingDeposit = document.forms["withdrawForm"]["wChecking"].value;
		var account = document.forms["withdrawForm"]["account2"].value;
		withdraw(account, checkingDeposit, relation); 
		
	});
	$("#transferForm").on("submit", function() {
		event.preventDefault();
		var transfer = document.forms["transferForm"]["transfer"].value;
		var account_1 = document.forms["transferForm"]["account-1"].value;
		var account_2 = document.forms["transferForm"]["account-2"].value;
		if(account_1 == "tChecking") {
			transferBalance(account_1, account_2, transfer, true, relation);
		} else {
			transferBalance(account_1, account_2, transfer, false, relation);

		}
	});
	$("#account-1").on("change", function() {
		var account = document.forms["transferForm"]["account-1"].value;
		if(account == "tChecking")
			document.forms["transferForm"]["account-2"].value = "tSavings"; 
		else
			document.forms["transferForm"]["account-2"].value = "tChecking"; 
	});
	$("#account-2").on("change", function() {
		var account = document.forms["transferForm"]["account-2"].value;
		if(account == "tChecking")
			document.forms["transferForm"]["account-1"].value = "tSavings"; 
		else
			document.forms["transferForm"]["account-1"].value = "tChecking"; 
	});
	$("#loanForm").on("submit", function() {
		event.preventDefault();
		var loan = document.forms["loanForm"]["loan"].value;
		var years = document.forms["loanForm"]["years"].value;
		var interest = document.forms["loanForm"]["interest"].value;
		var payment = getMonthlyPayment(loan, interest, years);
		$("#payment").html(payment);
		$("#monthlyPayment").show();
	});
	$("#interest").on("change", function() {
		$("#interest_slider").val($("#interest").val());
	});
	$("#years").on("change", function() {
		if($("#years").val() < 9) {
			$("#interest").attr("min", "0.89");
			$("#interest").val("0.89");
			$("#interest_slider").attr("min", "0.89");
			$("#interest_slider").val("0.89");
		}else {
			$("#interest").attr("min", "1.98");
			$("#interest").val("1.98");
			$("#interest_slider").attr("min", "1.98");
			$("#interest_slider").val("1.98");
		}
	});
	$("#interest_slider").on("input", function() {
		$("#interest").val($("#interest_slider").val());	
		var loan = document.forms["loanForm"]["loan"].value;
		var years = document.forms["loanForm"]["years"].value;
		var interest = document.forms["loanForm"]["interest"].value;
		var payment = getMonthlyPayment(loan, interest, years);
		$("#payment").html(payment);
		$("#monthlyPayment").show();

	});
	$("#reset").click(function() {
		$.post("bank-3.php", {reset: "reset"}, function(response) {
			getAccountBalance();
		});	
		deleteDB(relation);
		dtable.clear().draw();
	});
});
</script>
	</head>	
	<body>
<div>
  <nav class="navbar navbar-expand-md navbar-dark sticky-top bg-dark">
   <div class="navbar-header">
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="50" height="50" viewBox="0 0 300 300" xml:space="preserve">
<desc>Created with Fabric.js 4.2.0</desc>
<defs>
</defs>
<g transform="matrix(0 0 0 0 0 0)" id="e516472b-14be-4294-8be2-5095afb52137"  >
</g>
<g transform="matrix(1 0 0 1 150 150)" id="7a356a08-df42-495c-bf40-f6e091aaaf3e"  >
<rect style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-dashoffset: 0; stroke-linejoin: miter; stroke-miterlimit: 4; fill: rgb(14,5,231); fill-rule: nonzero; opacity: 1;" vector-effect="non-scaling-stroke"  x="-150" y="-150" rx="0" ry="0" width="300" height="300" />
</g>
<g transform="matrix(1 0 0 1 150 90.78)" style="" id="e635aad1-b717-4adc-b067-60af8cc35a25"  >
		<text xml:space="preserve" font-family="Rokkitt" font-size="40" font-style="normal" font-weight="400" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-dashoffset: 0; stroke-linejoin: miter; stroke-miterlimit: 4; fill: rgb(242,236,236); fill-rule: nonzero; opacity: 1; white-space: pre;" ><tspan x="-112.28" y="-7.77" >First Bank Of</tspan><tspan x="-52.1" y="32.91" >HTML</tspan></text>
</g>
<g transform="matrix(1 0 0 1 150 206.36)" style="" id="4cc10159-26df-416c-ad56-770af1774384"  >
		<text xml:space="preserve" font-family="Rokkitt" font-size="90" font-style="normal" font-weight="400" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-dashoffset: 0; stroke-linejoin: miter; stroke-miterlimit: 4; fill: rgb(255,255,255); fill-rule: nonzero; opacity: 1; white-space: pre;" ><tspan x="-58.64" y="28.27" >UK</tspan></text>
</g>
</svg>
   </div>
	<ul class="navbar-nav nav nav-pills" role="tablist">
	<li class="nav-item active">
		<a class="nav-link active" id="tab-1" data-toggle="tab" href="#content-1" role="tab" aria-selected="false">Accounts</a>
	<li class="nav-item">
		<a class="nav-link" id="tab-2" data-toggle="tab" href="#content-2" role="tab" aria-selected="false">Deposit</a>
	<li class="nav-item">
		<a class="nav-link" id="tab-3" data-toggle="tab" href="#content-3" role="tab" aria-selected="false">Withdraw</a>
	<li class="nav-item">
		<a class="nav-link" id="tab-4" data-toggle="tab" href="#content-4" role="tab" aria-selected="false">Transfer</a>
	<li class="nav-item">
		<a class="nav-link" id="tab-5" data-toggle="tab" href="#content-5" role="tab" aria-selected="false">Loan</a>
</ul>


  </nav>
</div>
<div class="container-full">
	<div class="tab-content">
		<div class ="tab-pane fade show active" id="content-1" aria-labelledby="tab-1">
		<div class="row">
			<div class="col">
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="300" height="300" viewBox="0 0 300 300" xml:space="preserve">
<desc>Created with Fabric.js 4.2.0</desc>
<defs>
</defs>
<g transform="matrix(0 0 0 0 0 0)" id="e516472b-14be-4294-8be2-5095afb52137"  >
</g>
<g transform="matrix(1 0 0 1 150 150)" id="7a356a08-df42-495c-bf40-f6e091aaaf3e"  >
<rect style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-dashoffset: 0; stroke-linejoin: miter; stroke-miterlimit: 4; fill: rgb(14,5,231); fill-rule: nonzero; opacity: 1;" vector-effect="non-scaling-stroke"  x="-150" y="-150" rx="0" ry="0" width="300" height="300" />
</g>
<g transform="matrix(1 0 0 1 150 90.78)" style="" id="e635aad1-b717-4adc-b067-60af8cc35a25"  >
		<text xml:space="preserve" font-family="Rokkitt" font-size="40" font-style="normal" font-weight="400" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-dashoffset: 0; stroke-linejoin: miter; stroke-miterlimit: 4; fill: rgb(242,236,236); fill-rule: nonzero; opacity: 1; white-space: pre;" ><tspan x="-112.28" y="-7.77" >First Bank Of</tspan><tspan x="-52.1" y="32.91" >HTML</tspan></text>
</g>
<g transform="matrix(1 0 0 1 150 206.36)" style="" id="4cc10159-26df-416c-ad56-770af1774384"  >
		<text xml:space="preserve" font-family="Rokkitt" font-size="90" font-style="normal" font-weight="400" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-dashoffset: 0; stroke-linejoin: miter; stroke-miterlimit: 4; fill: rgb(255,255,255); fill-rule: nonzero; opacity: 1; white-space: pre;" ><tspan x="-58.64" y="28.27" >UK</tspan></text>
</g>
</svg>
</div>
<div class="col-9">
				<h1 style="text-align: center;">Accounts</h1>	

				<table class="table table-striped table-hover table-bordered" id="accountTable">
					<thead>
					<tr>
						<th scope="col"><b>Checking</b></th>
						<th scope="col"><b>Savings</b></th>
					</tr>
					</thead>
					<tbody>
					<tr>
						<td id="checking"></td>
						<td id="savings"></td>
					</tr>
					</tbody>
				</table>
			</div>
			<div class="col"></div>
			</div>
			<div>
				<h1 style="text-align: center">Transactions</h1>
				<table id="transaction" class="table-striped table-hover table-bordered">
					<thead>
						<tr>
							<th>Type</th>
							<th>Source</th>
							<th>Destination</th>
							<th>Amount</th>
						</tr>
					</thead>
				</table>

			</div>
		<button id="reset">reset</button>
		</div>
		<div class="tab-pane fade" id="content-2" aria-labelledby="tab-2">

			<form id="depositForm" name="myform" class="form-horizontal">
				<div class="form-group"> 
					<label class="control-label col-sm-3">Deposit money into:</label>
					<div class="col-sm-offset-2 col-sm-4">
					<select class="form-control" id="account" name="account" data-toggle="tooltip" data-placement="top" title="Select account for deposit">
						<option value="dChecking">Checking</option>
						<option value="dSavings">Savings</option>
					</select>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3">Amount</label>
					<div class="col-sm-9">
						<input type="text" class="form-control" id="dChecking" name="dChecking" data-toggle="tooltip" data-placement="top" title="Enter amount to deposit">
					</div>
				</div>
				<div class="form-group">
					<div class="col-sm-offset-2 col-sm-10">
					<button type="submit" class="btn btn-primary">Submit</button>
					</div>
				</div>
			</form>
		</div>
		<div class="tab-pane fade" id="content-3" aria-labelledby="tab-3">

			<form id="withdrawForm" name="myform" class="form-horizontal">
				<div class="form-group"> 
					<label class="control-label col-sm-3">Withdraw money from:</label>
					<div class="col-sm-offset-2 col-sm-4">
					<select class="form-control" id="account2" name="account" data-toggle="tooltip" data-placement="top" title="Select account for deposit">
						<option value="wChecking">Checking</option>
						<option value="wSavings">Savings</option>
					</select>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3">Amount</label>
					<div class="col-sm-9">
						<input type="text" class="form-control" id="wChecking" name="wChecking" data-toggle="tooltip" data-placement="top" title="Enter amount to withdraw">
					</div>
				</div>
				<div class="form-group">
					<div class="col-sm-offset-2 col-sm-10">
					<button type="submit" class="btn btn-primary">Submit</button>
					</div>
				</div>
			</form>
		</div>
		<div class="tab-pane fade" id="content-4" aria-labelledby="tab-4">
			<form id="transferForm" class="form-horizontal">
				<div class="form-group"> 
					<label class="control-label col-sm-3">Transfer money from:</label>
					<div class="col-sm-offset-2 col-sm-4">
					<select class="form-control" id="account-1" name="account" data-toggle="tooltip" data-placement="top" title="Select account to transfer from">
						<option value="tChecking">Checking</option>
						<option value="tSavings">Savings</option>
					</select>
					</div>
				</div>
				<div class="form-group"> 
					<label class="control-label col-sm-3">Transfer money to:</label>
					<div class="col-sm-offset-2 col-sm-4">
					<select class="form-control" id="account-2" name="account" data-toggle="tooltip" data-placement="top" title="Select account to transfer to">
						<option value="tSavings">Savings</option>
						<option value="tChecking">Checking</option>
					</select>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3">Amount</label>
					<div class="col-sm-9">
						<input type="text" class="form-control" id="transfer" name="transfer" data-toggle="tooltip" data-placement="top" title="Enter amount to transfer">
					</div>
				</div>
				<div class="form-group">
					<div class="col-sm-offset-2 col-sm-10">
					<button type="submit" class="btn btn-primary">Submit</button>
					</div>
				</div>
			</form>

		</div>
		<div class="tab-pane fade" id="content-5" aria-labelledby="tab-5">
			<form id="loanForm" class="form-horizontal">
				<div class="form-group"> 
					<label class="control-label col-sm-3">Loan amount:</label>
					<div class="col-sm-offset-2 col-sm-4">
					<input type="number" min="500.00" step="0.01" value="500.00" id="loan" class="form-control" placeholder="Price" data-toggle="tooltip" data-placement="top" title="Enter loan amount minimum is $500">
					</div>
				</div>
				<div class="form-group"> 
					<label class="control-label col-sm-3">Number of years:</label>
					<div class="col-sm-offset-2 col-sm-4">
					<select class="form-control" id="years" data-toggle="tooltip" data-placement="top" title="Select the number of years.">
						<option value="5">5</option>
						<option value="6">6</option>
						<option value="15">15</option>
						<option value="30">30</option>
					</select>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3">Interest rate:</label>
					<div class="col-sm-4">
						<input type="number" min="0.89" step="0.01" value="0.89" class="form-control" id="interest" data-toggle="tooltip" data-placement="top" title="Enter desired interest rate">
					</div>
				</div>
				<div class="col-sm-offset-2 col-sm-4">
					<input type="range" class="form-range" min="0.89" max="100" step="0.01" value="0.89" id="interest_slider" style="width: 100%" data-toggle="tooltip" data-placement="top" title="Use the slider to see how different interest rates effect monthly payments."> 
				</div>
				<div class="form-group">
					<div class="col-sm-offset-2 col-sm-10">
					<button type="submit" class="btn btn-primary">Submit</button>
					</div>
				</div>
			</form>
			<div id="monthlyPayment" class="col-sm-offset-2 col-sm-10">
				<span>Monthly Payment: $</span>
				<span id="payment"></span>
			</div>
		</div>
	</div>
</div>

	</body>
</html>

