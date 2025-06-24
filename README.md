# CustomLLM

A full-stack, customized AI chat web application built with Laravel 12, Inertia, Vue 3 (Composition API) and Tailwind CSS. It lets you chat with today’s best large language models, stream their answers in real time, and shape their personality to fit your workflow.

---

## Key Features

- **Model Selector**  
  Switch between GPT-4o, Gemini 2.5 Flash, Claude 3 Opus and any future model from a single dropdown. Each chat remembers which model you picked.

- **Conversation History**  
  Your chats are saved automatically; the first user message is turned into a friendly title so you can find discussions later.

- **Real-Time Streaming**  
  Tokens appear on screen as soon as they are generated, creating a smooth, “typing” effect without waiting for the whole answer to finish.

- **Personalized Instructions**  
  A dedicated panel lets you define tone, style, domain knowledge and other guidelines. Every future response respects these custom rules, making the assistant feel truly yours.

- **Secure & Collaborative**  
  Built-in authentication, team support and role permissions help you share the tool with colleagues while keeping data private.

- **Elegant UI**  
  Responsive, cyberpunk-style interface made with Tailwind CSS.

---

## Quick Start
```bash
git clone https://github.com/your-org/customllm.git
cd customll
m composer install
npm install
cp .env.example .env      # add your API keys
php artisan key:generate
php artisan migrate
npm run dev
```

Open `http://localhost:8000`, create an account and start chatting!

---

## Roadmap

- Group conversations
- Image generation
- Local knowledge base (RAG)  
- Export to Markdown / PDF  
- Voice input & multimodal output  

---

## License

Released under the MIT License.

Enjoy the conversation!
