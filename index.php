<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }

        #chat-container {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            max-width: 600px;
            margin: 0 auto;
            height: 100vh;
            background-color: white;
            border-radius: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideFade 1s forwards;
        }

        #chat-box {
            padding: 20px;
            overflow-y: auto;
            flex-grow: 1;
            max-height: calc(100vh - 140px);
            animation: slideFade 1s forwards;
        }

        .user-message,
        .bot-message {
            display: block;
            max-width: 100%;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 10px;
            animation: slideFade 1s forwards;
        }

        .user-message {
            background-color: #f1f1f1;
            color: #66ccff;
            align-self: flex-end;
            text-align: right;
        }

        .bot-message {
            background-color: #66ccff;
            color: white;
            align-self: flex-start;
        }

        #input-container {
            display: flex;
            padding: 0px;
            background-color: #66ccff;
            border-top: 1px solid #ddd;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            animation: slideFade 0.3s forwards;
            width: 100%;
            box-sizing: border-box;
        }

        #prompt {
            width: 100%;
            padding: 10px;
            border-radius: 40px;
            border: 1px solid #ccc;
            font-size: 16px;
            background-color: white;
            color: #66ccff;
        }

        #generateButton {
            padding: 2px;
            background-color: #66ccff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
        }

        #generateButton:hover {
            background-color: #66ccff;
        }

        #apiKeyInput {
            display: none;
        }

        #response {
            display: none;
        }

        .generating-message {
            font-style: italic;
            color: #888;
            animation: slideFade 1s forwards;
        }

        @keyframes slideFade {
            0% {
                opacity: 0;
                transform: translateY(50px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            #input-container {
                width: 90%;
                padding: 15px;
            }

            #generateButton {
                padding: 12px;
            }
        }

        @media (max-width: 480px) {
            #input-container {
                width: 100%;
                padding: 10px;
            }

            #generateButton {
                padding: 10px;
            }
        }

        .loading-dots::after {
            content: '';
            animation: dots 1s steps(3, end) infinite;
        }

        @keyframes dots {
            0%,
            20% {
                content: '.';
            }

            40% {
                content: '..';
            }

            60%,
            100% {
                content: '...';
            }
        }
    </style>
</head>

<body>

    <div id="chat-container">
        <div id="chat-box">
        </div>

        <div id="input-container">
            <input type="password" id="apiKey" placeholder="Enter your API key" style="display:none;">
            <textarea id="prompt" placeholder="tanya sesuatu.." rows="1" style="width: 100%;"></textarea>
            <button id="generateButton" style="width: 44%; padding: 10px;">>_</button>
        </div>

    </div>

    <script>
        document.getElementById("apiKey").value = "AIzaSyCSotisjfFRx7-M6zVUg66pBXvnGfPlQpk";

        const apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent";
        const chatBox = document.getElementById("chat-box");
        const promptTextArea = document.getElementById("prompt");
        const generateButton = document.getElementById("generateButton");
        const apiKeyInput = document.getElementById("apiKey");
        let generatingMessage;

        function appendMessage(text, isBot = false) {
            const messageDiv = document.createElement("div");
            messageDiv.classList.add(isBot ? "bot-message" : "user-message");
            messageDiv.textContent = text;
            chatBox.appendChild(messageDiv);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        const loadingMessage = document.createElement("div");
        loadingMessage.classList.add("bot-message", "generating-message", "loading-dots");
        loadingMessage.textContent = "Memuat";
        chatBox.appendChild(loadingMessage);

        setTimeout(() => {
            chatBox.removeChild(loadingMessage);
            appendMessage("Halo..", true);
        }, 2000);

        function formatHTML(html) {
            html = html.replace(/<[^/][^>]*>/g, function(tag) {
                return tag + '\n';
            });
            html = html.replace(/^\s*[\r\n]/gm, "");
            html = html.split('\n').map(line => line.trim()).join('\n');

            return html;
        }

        async function generateResponse(promptText) {
            const apiKey = apiKeyInput.value;

            if (!apiKey) {
                appendMessage("Please enter your API key.", true);
                return;
            }

            if (!promptText) {
                appendMessage("tanya apa?", true);
                return;
            }

            appendMessage(promptText, false);

            generatingMessage = document.createElement("div");
            generatingMessage.classList.add("generating-message", "loading-dots");
            generatingMessage.textContent = "mmm";
            chatBox.appendChild(generatingMessage);
            chatBox.scrollTop = chatBox.scrollHeight;

            const requestBody = {
                contents: [{
                    parts: [{
                        text: promptText
                    }]
                }]
            };

            try {
                const response = await fetch(`${apiUrl}?key=${apiKey}`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(requestBody),
                });

                if (!response.ok) {
                    let errorMessage = `API Error: ${response.status} - ${response.statusText}`;
                    try {
                        const errorData = await response.json();
                        errorMessage += `\nDetails: ${JSON.stringify(errorData)}`;
                    } catch (jsonError) {
                    }
                    appendMessage(errorMessage, true);
                    return;
                }

                const data = await response.json();

                let generatedText = "";
                if (data.candidates && data.candidates.length > 0) {
                    if (data.candidates[0].content && data.candidates[0].content.parts && data.candidates[0].content.parts.length >
                        0) {
                        generatedText = data.candidates[0].content.parts[0].text;
                    } else {
                        generatedText = "terjadi galat, coba lagi..";
                    }
                } else {
                    generatedText = "terjadi galat, coba lagi..";
                }

                if (generatedText.toLowerCase().includes("<!doctype html>") ||
                    generatedText.toLowerCase().includes("<html")) {
                    generatedText = formatHTML(generatedText);
                }

                generatedText = generatedText.replace(/google(?!\s\w{7,})/gi, "G");

                appendMessage(generatedText, true);

            } catch (error) {
                console.error("Fetch error:", error);
                appendMessage("An error occurred: " + error.message, true);
            } finally {
                chatBox.removeChild(generatingMessage);
            }
        }

        generateButton.addEventListener("click", async () => {
            const promptText = promptTextArea.value;

            if (/apa\s+model\s+kamu\?/i.test(promptText)) {
                appendMessage("aidiyau", true);
            }
            else if (/siapa\s+membuat\s+kamu\?/i.test(promptText)) {
                appendMessage("Rahasia.", true);
            }
            else if (/i\s+don't\s+have\s+a\s+name/i.test(promptText)) {
                appendMessage("Aidiyau", true);
            }
            else if (/saya\s+adalah\s+model\s+bahasa\s+besar/i.test(promptText)) {
                appendMessage("Aidiyau", true);
            }
            else if (/jam\s+sekarang|jam/i.test(promptText)) {
                const now = new Date();
                const timeString = now.toLocaleTimeString();
                appendMessage(`Sekarang jam: ${timeString}`, true);
            }
            else if (/siapa\s+nama\s+kepala\s+sman\s+4\s+muara\s+teweh/i.test(promptText)) {
                appendMessage("Taryana", true);
            }
            else {
                await generateResponse(promptText);
            }

            promptTextArea.value = "";
        });

    </script>
</body>

</html>
