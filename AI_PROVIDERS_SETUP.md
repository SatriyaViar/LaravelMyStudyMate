# AI Providers Setup Guide

## 🤖 Supported AI Providers

Aplikasi ini mendukung 3 AI provider untuk generate quiz:

### 1. **DeepSeek** (Recommended - Murah & Bagus)

**Keuntungan:**
- ✅ Sangat murah ($0.14 per 1M input tokens, $0.28 per 1M output)
- ✅ Kualitas bagus setara GPT-3.5
- ✅ Rate limit lebih tinggi
- ✅ Support bahasa Indonesia dengan baik

**Setup:**
1. Daftar di https://platform.deepseek.com/
2. Buat API key di dashboard
3. Update `.env`:
```env
AI_PROVIDER=deepseek
DEEPSEEK_API_KEY=sk-xxxxxxxxxxxxxxxx
DEEPSEEK_MODEL=deepseek-chat
```

**Pricing:**
- Input: $0.14 / 1M tokens (~$0.00014 per quiz)
- Output: $0.28 / 1M tokens (~$0.00028 per quiz)
- **Total per quiz: ~$0.0004 (Rp 6)**

---

### 2. **Google Gemini** (Gratis tapi Limited)

**Keuntungan:**
- ✅ GRATIS untuk tier free
- ✅ Kualitas sangat bagus (Gemini 2.0)
- ❌ Rate limit ketat: 15 requests/minute, 1500/day

**Setup:**
Sudah aktif dengan API key yang ada:
```env
AI_PROVIDER=gemini
GEMINI_API_KEY=Axxxxxxx
```

**Limits:**
- 15 requests per minute
- 1,500 requests per day
- Quota reset setiap 24 jam

---

### 3. **OpenAI** (Paling Mahal)

**Keuntungan:**
- ✅ Kualitas terbaik (GPT-4)
- ✅ Very reliable
- ❌ Mahal: $5 minimum deposit

**Setup:**
1. Daftar di https://platform.openai.com/
2. Top up minimum $5
3. Update `.env`:
```env
AI_PROVIDER=openai
OPENAI_API_KEY=sk-xxxxxxxxxxxxxxxx
OPENAI_MODEL=gpt-4
```

**Pricing:**
- GPT-4: $0.03 per 1K tokens input, $0.06 per 1K tokens output
- **Total per quiz: ~$0.06 (Rp 900)**

---

## 🚀 Quick Start dengan DeepSeek

1. **Daftar DeepSeek:**
   - Kunjungi: https://platform.deepseek.com/sign_up
   - Sign up dengan email/Google
   - Verifikasi email

2. **Generate API Key:**
   - Login ke dashboard
   - Klik "API Keys" di sidebar
   - Klik "Create API Key"
   - Copy API key (sk-xxxxxxxxxx)

3. **Update `.env`:**
```bash
cd PBLMobile
nano .env  # atau buka dengan text editor
```

Ubah:
```env
AI_PROVIDER=deepseek
DEEPSEEK_API_KEY=sk-your-actual-api-key-here
```

4. **Restart Server:**
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

5. **Test Generate Quiz:**
   - Buka Flutter app
   - Create study card
   - Generate quiz
   - Soal akan di-generate oleh DeepSeek!

---

## 📊 Comparison

| Provider | Cost/Quiz | Quality | Rate Limit | Recommended For |
|----------|-----------|---------|------------|----------------|
| **DeepSeek** | Rp 6 | ⭐⭐⭐⭐ | High | **Production** |
| Gemini | FREE | ⭐⭐⭐⭐⭐ | Very Limited | Development |
| OpenAI | Rp 900 | ⭐⭐⭐⭐⭐ | High | Enterprise |

---

## 🔧 Troubleshooting

### Error: "Quota exceeded"
- **Gemini**: Tunggu 24 jam atau switch ke DeepSeek
- **DeepSeek**: Top up balance
- **OpenAI**: Top up balance

### Error: "Invalid API key"
- Cek API key di dashboard provider
- Pastikan tidak ada spasi di awal/akhir
- Generate ulang jika perlu

### Error: "Model not found"
- Gemini: Gunakan `gemini-2.0-flash`
- DeepSeek: Gunakan `deepseek-chat`
- OpenAI: Gunakan `gpt-4` atau `gpt-3.5-turbo`

---

## 💡 Recommendation

Untuk production, **gunakan DeepSeek** karena:
- Harga sangat murah (100x lebih murah dari GPT-4)
- Kualitas bagus
- Rate limit tinggi
- No quota restriction seperti Gemini

Estimasi biaya untuk 1000 quiz/bulan: **Rp 6,000** (DeepSeek) vs **Rp 900,000** (OpenAI)
