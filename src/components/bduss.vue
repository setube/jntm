<template>
  <el-main
    v-loading="loading"
    class="main"
  >
    <el-alert
      v-if="cardscan"
      title="操作步骤"
      :description="!isMobile ? '打开百度网盘APP -> 右上角打开相机识别二维码' : '长按保存二维码 -> 打开百度网盘APP -> 相册导入二维码图片'"
      :closable="false"
      type="success"
      center
    />
    <el-result
      v-if="cardsuccess"
      icon="success"
      title="扫描成功"
      sub-title="请确认登录"
    />
    <el-image
      v-if="cardscan"
      :src="QRcode"
      class="QRcode"
      @load="onImageLoad"
    />
    <el-result
      v-if="state.error"
      icon="error"
      title="操作超时"
      sub-title="请重新获取"
    >
      <template slot="extra">
        <el-button
          type="primary"
          @click="init"
        >
          重新获取
        </el-button>
      </template>
    </el-result>
  </el-main>
</template>

<script>
import axios from 'axios';

export default {
    name: 'BdussPanel',
    data () {
        return {
            state: {
                error: false,
                success: false
            },
            QRcode: '',
            loading: true,
            cardscan: true,
            isMobile: false,
            controller: {
                bduss: [new AbortController()],
                qrcode: new AbortController()
            },
            cardsuccess: false
        };
    },
    created () {
        this.checkWindowSize();
        window.addEventListener('resize', this.checkWindowSize);
    },
    beforeDestroy () {
        window.removeEventListener('resize', this.checkWindowSize);
    },
    mounted () {
        this.init();
    },
    methods: {
        onImageLoad () {
            this.loading = false;
        },
        checkWindowSize () {
            this.isMobile = window.innerWidth <= 768;
        },
        init () {
            this.loading = true;
            this.cardscan = true;
            this.state.error = false;
            this.controller.qrcode.abort();
            this.controller.qrcode = new AbortController();
            axios.get('/API/api.php?m=getqrcode').then(res => {
                if (res.data.errno !== 0) {
                    this.cardscan = false;
                    this.state.error = true;
                    return;
                }
                this.QRcode = 'https://' + res.data.data.imgurl;
                this.getStatus(1, res.data.data.sign);
            }).catch(() => {
                this.cardscan = false;
                this.state.error = true;
            });
        },
        getStatus (status, sign) {
            if (status > 1 || status < -1) {
                return;
            }
            this.controller.bduss[this.controller.bduss.length - 1].abort();
            this.controller.bduss.push(new AbortController());
            axios.get(`/API/api.php?m=getbduss&full=1&sign=${sign}`, {
                cancelToken: this.controller.bduss[this.controller.bduss.length - 1].token
            }).then(res => {
                res = res.data;
                if (res?.errno !== 0) {
                    this.cardscan = false;
                    this.state.error = true;
                    return;
                }
                if (res.data.status >= 0 && res.data.status < 2) {
                    if (res.data.status === 0) {
                        this.cardscan = false;
                        this.cardsuccess = true;
                    }
                    this.getStatus(res.data.status, sign);
                } else if (res.data.status === -1) {
                    this.cardscan = false;
                    this.state.error = true;
                } else if (res.data.status === 2) {
                    this.$emit('my-bduss', res.data.bduss);
                    this.$message({
                        type: 'success',
                        message: '获取成功'
                    });
                    this.$store.state.active = '2';
                } else {
                    this.cardscan = false;
                    this.state.error = true;
                }
            }).catch(() => {
                if (!this.controller.bduss[this.controller.bduss.length - 2].token.reason) {
                    this.cardscan = false;
                    this.state.error = true;
                }
            });
        },
        escapeHTML (unsafe_str) {
            return unsafe_str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/\//g, '&#x2F;');
        }
    }
};
</script>

<style scoped>
    .main {
        text-align: center;
    }
    .main .QRcode {
        width: 200px;
        margin: 1em auto 0 auto;
    }
</style>