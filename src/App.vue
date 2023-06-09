<template>
  <div id="app">
    <el-tabs
      v-model="$store.state.active"
      class="tabs"
    >
      <el-tab-pane
        label="一键转存"
        name="1"
      >
        <el-main v-loading="loading">
          <el-form
            label-position="right"
            label-width="80px"
          >
            <!--<el-form-item label="BDUSS">-->
            <!--    <el-input v-model="bduss" minlength="10" placeholder="请输入你的百度BDUSS"></el-input>-->
            <!--</el-form-item>-->
            <el-form-item label="网盘路径">
              <el-input
                v-model="path"
                placeholder="保存文件的路径，留空默认为根目录"
              />
            </el-form-item>
            <el-form-item label="分享链接">
              <el-input
                v-model="url"
                type="textarea"
                :autosize="{minRows: 4}"
                placeholder="bdpan://开头的专用分享链接，一行一个"
              />
            </el-form-item>
          </el-form>
          <el-button
            type="primary"
            style="width: 100%;"
            @click="saveFile"
          >
            立即转存
          </el-button>
          <el-table
            v-if="tableshow"
            :data="tableData"
            border
            class="tableData"
            :row-class-name="setRowClassName"
          >
            <el-table-column
              prop="filename"
              label="文件名"
            />
            <el-table-column
              prop="msg"
              label="转存结果"
            />
          </el-table>
        </el-main>
      </el-tab-pane>
      <el-tab-pane
        label="一键分享"
        name="2"
      >
        <el-alert
          :closable="false"
          title="请先将要分享的文件上传到百度网盘，然后将要分享的文件拖到下方区域，即可生成bdpan://文件分享链接。"
          type="success"
        />
        <el-alert
          :closable="false"
          title="文件分享链接的计算生成全部在浏览器完成，不会产生任何网络流量。生成时间视文件大小而定，如果文件较大请耐心等待，理论支持3G以下的文件。"
          type="info"
          style="margin-top: 10px;"
        />
        <el-upload
          class="upload"
          drag
          action="/"
          multiple
          :before-upload="File"
        >
          <i class="el-icon-upload" />
          <div class="el-upload__text">
            将文件拖到此处，或<em>点击上传</em>
          </div>
          <div
            slot="tip"
            class="el-upload__tip"
          >
            文件拖放到这里或者点击选择文件 <strong>计算文件分享链接</strong>
          </div>
        </el-upload>
        <template v-if="links">
          <el-card class="upload">
            <div
              slot="header"
              class="clearfix"
            >
              <span>已生成的分享链接：</span>
            </div>
            <div class="card">
              <el-input
                v-model="links"
                type="textarea"
                :autosize="{minRows: 3}"
              />
            </div>
          </el-card>
          <div class="upload">
            <el-button
              type="primary"
              class="copy"
              @click="copy"
            >
              复制全部链接
            </el-button>
            <el-button
              type="danger"
              @click="links = ''"
            >
              清空生成记录
            </el-button>
          </div>
        </template>
      </el-tab-pane>
      <el-tab-pane
        label="获取BDUSS"
        name="3"
      >
        <el-tabs
          v-if="$store.state.active == 3"
          v-model="active"
          :tab-position="isMobile"
        >
          <bduss @my-bduss="bdussData" />
        </el-tabs>
      </el-tab-pane>
      <el-tab-pane
        label="查看帮助"
        name="4"
      >
        <el-card
          class="upload"
          shadow="hover"
        >
          <div
            slot="header"
            class="clearfix"
          >
            <span>百度网盘免和谐分享介绍</span>
          </div>
          <p>
            百度网盘免和谐分享利用百度网盘秒传机制，实现任意文件分享链接生成与批量转存。在一键分享页面可以生成bdpan://专用链，该专用链包含文件的特征值；在一键转存页面可以输入别人分享的bdpan://专用链，批量转存到自己的网盘。
          </p>
        </el-card>
        <el-card
          class="upload"
          shadow="hover"
        >
          <div
            slot="header"
            class="clearfix"
          >
            <span>生成分享链接时浏览器卡住</span>
          </div>
          <p>生成分享链接需要 Chrome 、Edge 或 Chromium 内核的浏览器，不支持IE浏览器。如果文件太大请耐心等待，具体生成时间与电脑配置有关。</p>
        </el-card>
        <el-card
          class="upload"
          shadow="hover"
        >
          <div
            slot="header"
            class="clearfix"
          >
            <span>一键转存时提示"该链接已失效"</span>
          </div>
          <p>说明该链接是无效链接，分享者在生成该链接的时候没有先将文件上传到百度网盘，或者百度网盘已经屏蔽该文件的上传。</p>
        </el-card>
        <el-card
          class="upload"
          shadow="hover"
        >
          <div
            slot="header"
            class="clearfix"
          >
            <span>如何分享多个文件夹</span>
          </div>
          <p>
            生成分享链接之后，在开头增加一条链接
            bdfolder://文件夹名称，这样这一条链接下面跟着的所有bdpan://链接都会转存到该文件夹，直到出现下一个bdfolder://链接定义新的目录为止。支持多级目录，例如
            <el-link
              type="primary"
              :underline="false"
            >
              bdfolder://我的应用/游戏/策略类游戏
            </el-link> ，转到根目录为 <el-link
              type="primary"
              :underline="false"
            >
              bdfolder://
            </el-link>
          </p>
        </el-card>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script>
import Qs from 'qs';
import axios from 'axios';
import bduss from './components/bduss.vue';
import CryptoJS from 'crypto-js';
import Clipboard from 'clipboard';
import POWERMODE from 'html-activate-power-mode';

const table = [0x00000000, 0x77073096, 0xEE0E612C, 0x990951BA, 0x076DC419, 0x706AF48F, 0xE963A535, 0x9E6495A3, 0x0EDB8832, 0x79DCB8A4, 0xE0D5E91E, 0x97D2D988, 0x09B64C2B, 0x7EB17CBD, 0xE7B82D07, 0x90BF1D91, 0x1DB71064, 0x6AB020F2, 0xF3B97148, 0x84BE41DE, 0x1ADAD47D, 0x6DDDE4EB, 0xF4D4B551, 0x83D385C7, 0x136C9856, 0x646BA8C0, 0xFD62F97A, 0x8A65C9EC, 0x14015C4F, 0x63066CD9, 0xFA0F3D63, 0x8D080DF5, 0x3B6E20C8, 0x4C69105E, 0xD56041E4, 0xA2677172, 0x3C03E4D1, 0x4B04D447, 0xD20D85FD, 0xA50AB56B, 0x35B5A8FA, 0x42B2986C, 0xDBBBC9D6, 0xACBCF940, 0x32D86CE3, 0x45DF5C75, 0xDCD60DCF, 0xABD13D59, 0x26D930AC, 0x51DE003A, 0xC8D75180, 0xBFD06116, 0x21B4F4B5, 0x56B3C423, 0xCFBA9599, 0xB8BDA50F, 0x2802B89E, 0x5F058808, 0xC60CD9B2, 0xB10BE924, 0x2F6F7C87, 0x58684C11, 0xC1611DAB, 0xB6662D3D, 0x76DC4190, 0x01DB7106, 0x98D220BC, 0xEFD5102A, 0x71B18589, 0x06B6B51F, 0x9FBFE4A5, 0xE8B8D433, 0x7807C9A2, 0x0F00F934, 0x9609A88E, 0xE10E9818, 0x7F6A0DBB, 0x086D3D2D, 0x91646C97, 0xE6635C01, 0x6B6B51F4, 0x1C6C6162, 0x856530D8, 0xF262004E, 0x6C0695ED, 0x1B01A57B, 0x8208F4C1, 0xF50FC457, 0x65B0D9C6, 0x12B7E950, 0x8BBEB8EA, 0xFCB9887C, 0x62DD1DDF, 0x15DA2D49, 0x8CD37CF3, 0xFBD44C65, 0x4DB26158, 0x3AB551CE, 0xA3BC0074, 0xD4BB30E2, 0x4ADFA541, 0x3DD895D7, 0xA4D1C46D, 0xD3D6F4FB, 0x4369E96A, 0x346ED9FC, 0xAD678846, 0xDA60B8D0, 0x44042D73, 0x33031DE5, 0xAA0A4C5F, 0xDD0D7CC9, 0x5005713C, 0x270241AA, 0xBE0B1010, 0xC90C2086, 0x5768B525, 0x206F85B3, 0xB966D409, 0xCE61E49F, 0x5EDEF90E, 0x29D9C998, 0xB0D09822, 0xC7D7A8B4, 0x59B33D17, 0x2EB40D81, 0xB7BD5C3B, 0xC0BA6CAD, 0xEDB88320, 0x9ABFB3B6, 0x03B6E20C, 0x74B1D29A, 0xEAD54739, 0x9DD277AF, 0x04DB2615, 0x73DC1683, 0xE3630B12, 0x94643B84, 0x0D6D6A3E, 0x7A6A5AA8, 0xE40ECF0B, 0x9309FF9D, 0x0A00AE27, 0x7D079EB1, 0xF00F9344, 0x8708A3D2, 0x1E01F268, 0x6906C2FE, 0xF762575D, 0x806567CB, 0x196C3671, 0x6E6B06E7, 0xFED41B76, 0x89D32BE0, 0x10DA7A5A, 0x67DD4ACC, 0xF9B9DF6F, 0x8EBEEFF9, 0x17B7BE43, 0x60B08ED5, 0xD6D6A3E8, 0xA1D1937E, 0x38D8C2C4, 0x4FDFF252, 0xD1BB67F1, 0xA6BC5767, 0x3FB506DD, 0x48B2364B, 0xD80D2BDA, 0xAF0A1B4C, 0x36034AF6, 0x41047A60, 0xDF60EFC3, 0xA867DF55, 0x316E8EEF, 0x4669BE79, 0xCB61B38C, 0xBC66831A, 0x256FD2A0, 0x5268E236, 0xCC0C7795, 0xBB0B4703, 0x220216B9, 0x5505262F, 0xC5BA3BBE, 0xB2BD0B28, 0x2BB45A92, 0x5CB36A04, 0xC2D7FFA7, 0xB5D0CF31, 0x2CD99E8B, 0x5BDEAE1D, 0x9B64C2B0, 0xEC63F226, 0x756AA39C, 0x026D930A, 0x9C0906A9, 0xEB0E363F, 0x72076785, 0x05005713, 0x95BF4A82, 0xE2B87A14, 0x7BB12BAE, 0x0CB61B38, 0x92D28E9B, 0xE5D5BE0D, 0x7CDCEFB7, 0x0BDBDF21, 0x86D3D2D4, 0xF1D4E242, 0x68DDB3F8, 0x1FDA836E, 0x81BE16CD, 0xF6B9265B, 0x6FB077E1, 0x18B74777, 0x88085AE6, 0xFF0F6A70, 0x66063BCA, 0x11010B5C, 0x8F659EFF, 0xF862AE69, 0x616BFFD3, 0x166CCF45, 0xA00AE278, 0xD70DD2EE, 0x4E048354, 0x3903B3C2, 0xA7672661, 0xD06016F7, 0x4969474D, 0x3E6E77DB, 0xAED16A4A, 0xD9D65ADC, 0x40DF0B66, 0x37D83BF0, 0xA9BCAE53, 0xDEBB9EC5, 0x47B2CF7F, 0x30B5FFE9, 0xBDBDF21C, 0xCABAC28A, 0x53B39330, 0x24B4A3A6, 0xBAD03605, 0xCDD70693, 0x54DE5729, 0x23D967BF, 0xB3667A2E, 0xC4614AB8, 0x5D681B02, 0x2A6F2B94, 0xB40BBE37, 0xC30C8EA1, 0x5A05DF1B, 0x2D02EF8D];

const crc32 = (uint8Array, crc) => {
    if (crc == window.undefined) {
        crc = 0;
    }
    var n = 0;
    crc = crc ^ -1;
    for (var i = 0, iTop = uint8Array.byteLength; i < iTop; i++) {
        n = (crc ^ uint8Array[i]) & 0xFF;
        crc = crc >>> 8 ^ table[n];
    }
    return crc ^ -1;
};

const swapendian32 = (val) => {
    return ((val & 0xFF) << 24 |
            (val & 0xFF00) << 8 |
            val >> 8 & 0xFF00 |
            val >> 24 & 0xFF) >>> 0;
};

const arrayBufferToWordArray = (arrayBuffer) => {
    var u8 = new Uint8Array(arrayBuffer);
    var cp = [];
    var fullWords = Math.floor(arrayBuffer.byteLength / 4);
    var u32 = new Uint32Array(arrayBuffer, 0, fullWords);
    var bytesLeft = arrayBuffer.byteLength % 4;
    for (var i = 0; i < fullWords; ++i) {
        cp.push(swapendian32(u32[i]));
    }
    if (bytesLeft) {
        var pad = 0;
        for (i = bytesLeft; i > 0; --i) {
            pad = pad << 8;
            pad += u8[u8.byteLength - i];
        }
        for (; i < 4 - bytesLeft; ++i) {
            pad = pad << 8;
        }
        cp.push(pad);
    }
    return CryptoJS.lib.WordArray.create(cp, arrayBuffer.byteLength);
};

const decimalToHexString = (number) => {
    if (number < 0) {
        number = 0xFFFFFFFF + number + 1;
    }
    return number;
};

const progressiveRead = (file, work, done) => {
    var pos = 0;
    var reader = new FileReader();
    var chunkSize = 262144;
    const progressiveReadNext = () => {
        var end = Math.min(pos + chunkSize, file.size);
        reader.onload = (e) => {
            pos = end;
            work(e.target.result, pos, file);
            if (pos < file.size) {
                progressiveReadNext();
            } else {
                done(file);
            }
        };
        var slicedBlob;
        if (file.slice) {
            slicedBlob = file.slice(pos, end);
        } else if (file.webkitSlice) {
            slicedBlob = file.webkitSlice(pos, end);
        }
        reader.readAsArrayBuffer(slicedBlob);
    };
    progressiveReadNext();
};

export default {
    components: {
        bduss
    },
    data () {
        return {
            url: '',
            path: '',
            links: '',
            bduss: '',
            loading: false,
            md5List: [],
            isMobile: false,
            tableshow: false,
            tableData: []
        };
    },
    watch: {
        bduss (val) {
            this.$store.commit('setBduss', val);
        },
        md5List (val) {
            this.links = val.map(item => {
                return `bdpan://|${item.name}|${item.md51}|${item.md52}|${item.data}|${item.size}|/`;
            }).join('\n');
        }
    },
    mounted () {
        if (this.$store.state.bduss) {
            this.bduss = this.$store.state.bduss;
            this.Getbduss();
        } else {
            this.$store.state.active = '3';
        }
    },
    methods: {
        bdussData (val) {
            this.bduss = val;
        },
        copy () {
            const clipboard = new Clipboard('.copy', {
                text: function () {
                    return document.querySelector('.card textarea').value;
                }
            });
            clipboard.on('success', () => {
                this.$message({
                    type: 'success',
                    message: '复制成功！'
                });
            });
            clipboard.on('error', () => {
                this.$message.error('复制失败，请长按链接后手动复制');
            });
        },
        File (f) {
            this.selectFile(f, this.md5List);
        },
        selectFile (f, md5List) {
            (function () {
                var slice = 0;
                var contentMd5 = CryptoJS.algo.MD5.create();
                var sliceMd5 = CryptoJS.algo.MD5.create();
                var crc32intermediate = 0;
                progressiveRead(f, (data) => {
                    var wordArray = arrayBufferToWordArray(data);
                    contentMd5.update(wordArray);
                    if (slice == 0) {
                        sliceMd5.update(wordArray);
                        slice = 1;
                    }
                    crc32intermediate = crc32(new Uint8Array(data), crc32intermediate);
                }, (file) => {
                    md5List = md5List.unshift({
                        md51: contentMd5.finalize(),
                        md52: sliceMd5.finalize(),
                        name: file.name,
                        size: file.size,
                        data: decimalToHexString(crc32intermediate)
                    });
                });
            })();
        },
        setRowClassName: (row) => {
            if (row.row.code === 0) {
                return 'success-row';
            } else if (row.row.code === -1) {
                return 'warning-row';
            } else if (row.row.code === -2) {
                return 'error-row';
            }
            return '';
        },
        saveFile () {
            if (!this.bduss || !this.url) {
                this.$message.error('请确保每项不能为空！');
                return;
            }
            if (this.path == '') {
                this.path = '/';
            }
            if (this.path.substr(0, 1) != '/') {
                this.$message.error('路径填写错误，路径如果需要自定义请以/开头');
                return;
            }
            if (this.path.substr(-1, 1) != '/') {
                this.path = this.path + '/';
            }
            let count = 0;
            let success = 0;
            this.url = this.url.replace(/\r\n/g, '*').replace(/\n/g, '*').replace(/\r/g, '*');
            let sum = this.url.split('*').length;
            this.url.split('*').forEach((v) => {
                const url = v.replace(/(^\s*)|(\s*$)/g, '');
                if (!v || !url || url.substr(0, 8) != 'bdpan://') {
                    sum--;
                    return true;
                }
                axios.post('/API/do.php?act=save', Qs.stringify({
                    path: this.path,
                    link: this.url,
                    bduss: this.bduss
                })).then(res => {
                    this.tableshow = true;
                    this.tableData.unshift({
                        msg: res.data.msg,
                        code: res.data.code,
                        filename: res.data.filename
                    });
                    if (++count === sum) {
                        if (success > 0) {
                            this.$alert(`本次成功转存${success}GetCovers个文件`, {
                                callback: () => {
                                    window.open(`https://pan.baidu.com/disk/home?#/all?path=${encodeURIComponent(this.path)}&vmode=list`);
                                },
                                cancelButtonText: '关闭',
                                confirmButtonText: '进入百度网盘查看'
                            });
                        }
                    }
                }).catch(() => {
                    this.$store.state.active = '4';
                    this.$store.commit('setBduss', '');
                    this.$message.error('当前BDUSS已经失效, 请重新获取');
                });
            });
        },
        Getbduss () {
            if (!this.bduss) {
                return;
            }
            this.loading = true;
            axios.post('/API/do.php?act=check', Qs.stringify({
                bduss: this.bduss
            })).then(res => {
                this.loading = false;
                this.$nextTick(() => {
                    POWERMODE.shake = false;
                    POWERMODE.colorful = true;
                    document.body.addEventListener('input', POWERMODE);
                });
                if (res.data.code == -1) {
                    this.$store.commit('setBduss', '');
                } else {
                    this.bduss = this.$store.state.bduss;
                }
            }).catch(() => {
                this.bduss = '';
                this.loading = false;
                this.$store.state.active = '4';
                this.$store.commit('setBduss', '');
            });
        }
    }
};
</script>

<style scoped>
    #app {
        margin: 0 auto;
        max-width: 768px;
    }

    .upload {
        margin-top: 20px;
    }

    .card {
        overflow: hidden;
        word-wrap: break-word;
    }

    .tableData {
        width: 100%;
        margin-top: 10px;
    }

    .iframe {
        width: 100%;
        min-height: 700px;
    }
</style>

<style>
    body::before {
        content: '';
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        opacity: .1;
        position: fixed;
        background-image: url('@/assets/bg.png');
        background-position: right 50px bottom 30px;
        background-size: auto 30vw;
        background-repeat: no-repeat;
        z-index: -1;
        pointer-events: none;
    }

    img {
        max-width: 100%;
        height: auto;
        object-fit: cover;
        border: 0;
        vertical-align: text-top
    }

    @media screen and (min-width: 768px) {
        .el-tabs__nav-scroll {
            display: flex;
            justify-content: center;
        }
    }

    @media screen and (max-width: 768px) {
        .el-tabs__item {
            padding: 0 10px;
        }
    }

    .el-tabs__nav-wrap:after {
        background-color: transparent;
    }

    .el-table .warning-row {
        background-color: #fdf6ec;
        color: #e6a23c;
    }

    .el-table .success-row {
        background-color: #f0f9eb;
        color: #67c23a;
    }

    .el-table .error-row {
        color: #f56c6c;
        background: #fef0f0;
    }

    .el-upload,
    .el-upload-dragger {
        width: 100%;
    }
</style>