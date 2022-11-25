<?php
$pageTitle = $load['title'] = 'Телеграм бот';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(196)) {
	die('E403R196');
}
?>
<script src="/sync/3rdparty/vue.min.js" type="text/javascript"></script>

<style>
	.messages {
		background-color: silver;
		max-height: 300px;
		min-height: 300px;
		overflow-y: scroll;
		padding: 3px;
		border-radius:  8px 0px 0px 8px;
		margin: 6px;
	}

	.message {
		border-radius: .4em;
		width: 80%;
		padding: 5px 10px;
		margin: 2px;
	}
	.messageI {
		float: left;
		background-color: white;
	}

	.messageO {
		float: right;
		background-color: #effdde;
	}

	.client {
		padding: 2px 10px;
		background-color: white;
		margin: 3px;
		border-radius: 4px;
		cursor: pointer;
		transition: 250ms ease-in background-color;
	}
	.client:hover {
		background-color: lightgoldenrodyellow;
	}
	.clients {
		max-height: 200px;
		overflow-y: scroll;
		border: 1px solid silver;
	}
	.time {
		text-align: right;
		font-size: 0.8em;
	}
	.messageInput{
		padding: 10px;
		resize: none;
		width: 100%;
		height: 100px;
	}
	.unread {
		background-color: red;
		color: white;
		width: 1.4em;
		height: 1.4em;
		border-radius: 50%;
		display: inline-flex;
		align-content: center;
		justify-content: center;
		font-size: 0.8em;
		font-weight: bold;
	}
</style>
<? include 'menu.php'; ?>

<div class="box neutral">
	<div class="box-body" id="vueapp" style=" max-width: 800px;">
		<div class="clients"> 
			<div v-for="client in clientsSorted" class="client">
				<span class="unread" v-if="client.unread">{{client.unread}}</span>
				<a :href="'/pages/offlinecall/schedule.php?client='+client.id" target="_blank"><i class="fas fa-external-link-alt"></i></a>
				<span @click="selectedClient=client.id">
					{{client.clientsLName}}
					{{client.clientsFName}}
					{{client.clientsMName}}
					({{client.messages.length}})
				</span>

			</div>

		</div>
		<div v-if="currentClient" style="padding: 20px; font-size: 1.5em;">
			<a :href="'/pages/offlinecall/schedule.php?client='+currentClient.id" target="_blank"><i class="fas fa-external-link-alt"></i></a>
			{{currentClient.clientsLName}}
			{{currentClient.clientsFName}}
			{{currentClient.clientsMName}}
		</div>
		<div class="messages" v-if="currentClient">
			<div class="messagesContent">

				<div v-if="currentClient" v-for="message in currentClient.messages" class="message" :class="{ messageI: message.type=='I', messageO:  message.type=='O' }">
					{{message.message}}
					<div class="time">

						{{message.user==' '?
							message.type=='O'?'🤖 ИНФИНИТИ МЕД БОТ':''
							:'👤 '+message.user}}

						{{message.time}}<span v-if="!message.readed"> NEW</span></div>
				</div>
				<div style=" clear: both;"></div>
			</div>
		</div>
		<textarea class="messageInput" v-model="message" v-if="currentClient">
			
		</textarea>
		<div style="padding: 10px;" v-if="currentClient">
			<button type="button" @click="send" style="width: 100%;">Отправить <i class="fab fa-telegram-plane"></i></button>	
		</div>

	</div>
</div>
<script src="includes/app.js" type="text/javascript" defer></script>
<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
